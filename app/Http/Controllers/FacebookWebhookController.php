<?php

namespace App\Http\Controllers;

use App\Models\PlatformPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    public function __construct()
    {
        // No dependencies needed
    }

    /**
     * Handle webhook verification
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        // Verify the webhook verification token
        if ($mode === 'subscribe' && $token === config('services.facebook.webhook_verify_token')) {
            // Return ONLY the challenge string with no additional content or headers
            return response($challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Verification failed', 403);
    }

    /**
     * Handle webhook notifications
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Verify the webhook signature
        if (!$this->verifySignature($request)) {
            return response('Invalid signature', 403);
        }

        // Process the webhook payload
        try {
            $this->processWebhook($payload);
            return response('Webhook processed', 200);
        } catch (\Exception $e) {
            Log::error('Error processing Facebook webhook', [
                'error' => $e->getMessage()
            ]);
            return response('Error processing webhook', 500);
        }
    }

    /**
     * Process webhook payload
     *
     * @param array $payload
     * @return void
     */
    protected function processWebhook(array $payload)
    {
        // Check if this is a page post metrics update
        if (isset($payload['entry']) && is_array($payload['entry'])) {
            foreach ($payload['entry'] as $entry) {
                if (isset($entry['changes']) && is_array($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        if ($change['field'] === 'feed' && isset($change['value']['post_id'])) {
                            $this->processPostMetricsUpdate('facebook', $change['value']);
                        } else if ($change['field'] === 'instagram_business_account' && isset($change['value']['media_id'])) {
                            $this->processPostMetricsUpdate('instagram', $change['value']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Process post metrics update
     *
     * @param string $provider
     * @param array $data
     * @return void
     */
    protected function processPostMetricsUpdate(string $provider, array $data)
    {
        $externalId = $provider === 'facebook' ? ($data['post_id'] ?? null) : ($data['media_id'] ?? null);

        if (!$externalId) {
            Log::warning("No external ID found for {$provider} metrics update", ['data' => $data]);
            return;
        }

        // Find the platform post by external_id and provider
        $platformPost = PlatformPost::whereHas('platform', function ($query) use ($provider) {
            $query->where('provider', $provider);
        })->where('external_id', $externalId)->first();

        if (!$platformPost) {
            Log::warning("No platform post found for {$provider} post with external ID: {$externalId}");
            return;
        }

        // Facebook webhooks don't reliably fire when metrics change, and when they do,
        // the data is often incomplete or delayed. Instead of trying to update metrics
        // directly from webhook data, we dispatch a job to fetch complete metrics from the API.
        // This ensures we get the most accurate and up-to-date metrics.
        \App\Jobs\UpdatePlatformPostMetrics::dispatch($platformPost);

        Log::info("Dispatched metrics update job for {$provider} post", [
            'platform_post_id' => $platformPost->id,
            'external_id' => $externalId
        ]);
    }

    /**
     * Verify webhook signature
     *
     * @param Request $request
     * @return bool
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, config('services.facebook.app_secret'));

        return hash_equals($expectedSignature, $signature);
    }
}

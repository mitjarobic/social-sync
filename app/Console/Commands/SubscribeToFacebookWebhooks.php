<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use JanuSoftware\Facebook\Facebook;
use Illuminate\Support\Facades\Log;

class SubscribeToFacebookWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facebook:subscribe-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to Facebook webhooks for metrics updates';

    /**
     * Note: This command only needs to be run ONCE during initial setup or when webhook configuration changes.
     * Webhook subscriptions persist on Facebook's side until manually deleted or the app is disabled.
     * Run this command again only if:
     * - Your application's domain/URL changes
     * - You want to listen for different webhook events
     * - Your webhook verification token changes
     * - The previous subscription was deleted or is no longer working
     */

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Subscribing to Facebook webhooks...');
        $this->info('Note: This is a one-time setup process. Webhook subscriptions persist on Facebook\'s side.');

        try {
            // For app-level operations like webhook subscriptions, we need to use an app access token
            // which is a combination of app_id|app_secret
            $appId = config('services.facebook.app_id');
            $appSecret = config('services.facebook.app_secret');
            $appAccessToken = $appId . '|' . $appSecret;

            // Initialize Facebook SDK with app access token
            $fb = new Facebook([
                'app_id' => $appId,
                'app_secret' => $appSecret,
                'default_access_token' => $appAccessToken,
                'default_graph_version' => 'v18.0',
            ]);

            // First, check if the webhook URL is accessible
            $webhookUrl = url('/api/webhooks/facebook');
            $this->info("Webhook URL: {$webhookUrl}");

            // Make sure the URL is HTTPS
            if (!str_starts_with($webhookUrl, 'https://')) {
                $this->warn("Warning: Facebook requires HTTPS for webhook URLs. Your URL is not using HTTPS.");
                $this->warn("If you're testing locally, use a service like ngrok to create an HTTPS tunnel.");
            }

            try {
                // Subscribe to page webhooks
                $this->info("Subscribing to page webhooks...");
                $response = $fb->post('/' . config('services.facebook.app_id') . '/subscriptions', [
                    'object' => 'page',
                    'callback_url' => $webhookUrl,
                    'fields' => 'feed',
                    'verify_token' => config('services.facebook.webhook_verify_token'),
                ]);

                $this->info('Subscribed to page webhooks: ' . json_encode($response->getDecodedBody()));
            } catch (\Exception $e) {
                $this->error('Failed to subscribe to page webhooks: ' . $e->getMessage());
                // Continue with Instagram subscription even if page subscription fails
            }

            try {
                // Subscribe to Instagram webhooks
                $this->info("Subscribing to Instagram webhooks...");
                $response = $fb->post('/' . config('services.facebook.app_id') . '/subscriptions', [
                    'object' => 'instagram',
                    'callback_url' => $webhookUrl,
                    'fields' => 'media',
                    'verify_token' => config('services.facebook.webhook_verify_token'),
                ]);

                $this->info('Subscribed to Instagram webhooks: ' . json_encode($response->getDecodedBody()));
            } catch (\Exception $e) {
                $this->error('Failed to subscribe to Instagram webhooks: ' . $e->getMessage());
                // Continue even if Instagram subscription fails
            }

            $this->info('Webhook subscription process completed.');
            $this->newLine();
            $this->info('✅ Webhook setup notes:');
            $this->info('  - You do not need to run this command again unless the configuration changes');
            $this->info('  - Verify your webhooks in the Facebook Developer Console');
            $this->info('  - Make sure your webhook URL is publicly accessible via HTTPS');
            $this->info('  - Test your webhooks by creating a post on Facebook or Instagram');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to initialize Facebook SDK: ' . $e->getMessage());
            $this->error('Please check your Facebook App ID and App Secret in your .env file.');

            // Log detailed error information
            Log::error('Failed to initialize Facebook SDK for webhook subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}

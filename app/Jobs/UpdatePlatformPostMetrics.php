<?php

namespace App\Jobs;

use App\Models\PlatformPost;
use App\Services\FacebookService;
use App\Services\InstagramService;
use App\Services\XService;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePlatformPostMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public PlatformPost $platformPost)
    {
    }

    /**
     * Get Instagram metrics using the user's Facebook token
     *
     * @param \App\Models\Platform $platform
     * @param string $externalId
     * @return array
     */
    private function getInstagramMetrics($platform, $externalId): array
    {
        // For Instagram, we need to use the user's Facebook token
        // First try to get the user from the platform post
        $user = $this->platformPost->user;

        // If that doesn't work, try to get it from the company
        if (!$user || !$user->facebook_token) {
            $company = $this->platformPost->company;
            if ($company && $company->user) {
                $user = $company->user;
            }
        }

        // If we still don't have a user with a token, log an error and return empty metrics
        if (!$user || !$user->facebook_token) {
            Log::error('No Facebook token found for Instagram metrics', [
                'platform_post_id' => $this->platformPost->id,
                'platform_id' => $platform->id,
                'external_id' => $externalId
            ]);

            return [
                'reach' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
            ];
        }

        // Now we have a user with a token, so we can get the metrics
        $instagramService = new InstagramService($user);
        return $instagramService->getMetrics(
            $platform->external_id,
            $externalId,
            $user->facebook_token
        );
    }

    public function handle(): void
    {
        // Load necessary relationships
        $this->platformPost->load(['platform', 'company.user', 'user']);

        // Only update metrics for posts with an external ID
        if (!$this->platformPost->external_id) {
            return;
        }

        // Only update metrics for published posts
        if ($this->platformPost->status !== \App\Enums\PlatformPostStatus::PUBLISHED) {
            Log::info('Skipping metrics update for non-published post', [
                'platform_post_id' => $this->platformPost->id,
                'status' => $this->platformPost->status,
            ]);
            return;
        }

        try {
            $platform = $this->platformPost->platform;
            $provider = $platform->provider;
            $externalId = $this->platformPost->external_id;
            $token = $platform->external_token;

            // Get metrics based on platform type using the appropriate service
            $metrics = match ($provider) {
                'facebook' => app(FacebookService::class)->getMetrics(
                    $platform->external_id,
                    $externalId,
                    $token
                ),
                'instagram' => $this->getInstagramMetrics($platform, $externalId),
                'x' => app(XService::class)->getMetrics(
                    $platform->external_id,
                    $externalId,
                    $token
                ),
                default => null
            };

            if ($metrics) {
                $this->platformPost->update([
                    'reach' => $metrics['reach'] ?? 0,
                    'likes' => $metrics['likes'] ?? 0,
                    'comments' => $metrics['comments'] ?? 0,
                    'shares' => $metrics['shares'] ?? 0,
                    'metrics_updated_at' => now(),
                ]);

                Log::info('Updated metrics for platform post', [
                    'platform_post_id' => $this->platformPost->id,
                    'platform' => $provider,
                    'metrics' => $metrics,
                ]);
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the job
            Log::error('Failed to update metrics for platform post: ' . $e->getMessage(), [
                'platform_post_id' => $this->platformPost->id,
                'platform' => $this->platformPost->platform->provider,
                'external_id' => $this->platformPost->external_id,
            ]);
        }
    }
}

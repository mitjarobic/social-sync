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
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Updating metrics for platform post');
        // Only update metrics for published posts with an external ID
        if ($this->platformPost->status !== \App\Enums\PlatformPostStatus::PUBLISHED || !$this->platformPost->external_id) {
            return;
        }

        try {
            $platform = $this->platformPost->platform;
            $provider = $platform->provider;
            $externalId = $this-> platformPost->external_id;
            $token = $platform->external_token;

            // Get metrics based on platform type using the appropriate service
            $metrics = match ($provider) {
                'facebook' => app(FacebookService::class)->getMetrics(
                    $platform->external_id,
                    $externalId,
                    $token
                ),
                'instagram' => app(InstagramService::class)->getMetrics(
                    $platform->external_id,
                    $externalId,
                    $token
                ),
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

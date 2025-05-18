<?php

namespace App\Services;

use App\Models\PlatformPost;
use Illuminate\Support\Facades\Log;

class MetricsUpdateService
{
    /**
     * Update Facebook post metrics
     * 
     * @param PlatformPost $platformPost
     * @param array $data
     * @return bool
     */
    public function updateFacebookMetrics(PlatformPost $platformPost, array $data): bool
    {
        try {
            // Extract metrics from the webhook data
            $metrics = [
                'reach' => $data['reach'] ?? $platformPost->reach,
                'likes' => $data['likes'] ?? $platformPost->likes,
                'comments' => $data['comments'] ?? $platformPost->comments,
                'shares' => $data['shares'] ?? $platformPost->shares,
            ];

            // If we have insights data, extract more detailed metrics
            if (isset($data['insights'])) {
                foreach ($data['insights'] as $insight) {
                    switch ($insight['name']) {
                        case 'post_impressions':
                        case 'post_impressions_unique':
                            $metrics['reach'] = $insight['values'][0]['value'] ?? $metrics['reach'];
                            break;
                        case 'post_reactions_by_type_total':
                            $metrics['likes'] = array_sum($insight['values'][0]['value'] ?? []) ?? $metrics['likes'];
                            break;
                        case 'post_comments':
                            $metrics['comments'] = $insight['values'][0]['value'] ?? $metrics['comments'];
                            break;
                        case 'post_shares':
                            $metrics['shares'] = $insight['values'][0]['value'] ?? $metrics['shares'];
                            break;
                    }
                }
            }

            // Update the platform post with the new metrics
            $platformPost->update([
                'reach' => $metrics['reach'],
                'likes' => $metrics['likes'],
                'comments' => $metrics['comments'],
                'shares' => $metrics['shares'],
                'metrics_updated_at' => now(),
            ]);

            Log::info('Updated Facebook metrics for platform post', [
                'platform_post_id' => $platformPost->id,
                'external_id' => $platformPost->external_id,
                'metrics' => $metrics
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating Facebook metrics', [
                'platform_post_id' => $platformPost->id,
                'external_id' => $platformPost->external_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Update Instagram post metrics
     * 
     * @param PlatformPost $platformPost
     * @param array $data
     * @return bool
     */
    public function updateInstagramMetrics(PlatformPost $platformPost, array $data): bool
    {
        try {
            // Extract metrics from the webhook data
            $metrics = [
                'reach' => $data['reach'] ?? $platformPost->reach,
                'likes' => $data['like_count'] ?? $platformPost->likes,
                'comments' => $data['comments_count'] ?? $platformPost->comments,
                'shares' => $data['shares'] ?? $platformPost->shares,
            ];

            // If we have insights data, extract more detailed metrics
            if (isset($data['insights'])) {
                foreach ($data['insights'] as $insight) {
                    switch ($insight['name']) {
                        case 'impressions':
                        case 'reach':
                            $metrics['reach'] = $insight['values'][0]['value'] ?? $metrics['reach'];
                            break;
                        case 'likes':
                            $metrics['likes'] = $insight['values'][0]['value'] ?? $metrics['likes'];
                            break;
                        case 'comments':
                            $metrics['comments'] = $insight['values'][0]['value'] ?? $metrics['comments'];
                            break;
                        case 'saved':
                            $metrics['shares'] = $insight['values'][0]['value'] ?? $metrics['shares'];
                            break;
                    }
                }
            }

            // Update the platform post with the new metrics
            $platformPost->update([
                'reach' => $metrics['reach'],
                'likes' => $metrics['likes'],
                'comments' => $metrics['comments'],
                'shares' => $metrics['shares'],
                'metrics_updated_at' => now(),
            ]);

            Log::info('Updated Instagram metrics for platform post', [
                'platform_post_id' => $platformPost->id,
                'external_id' => $platformPost->external_id,
                'metrics' => $metrics
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating Instagram metrics', [
                'platform_post_id' => $platformPost->id,
                'external_id' => $platformPost->external_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }
}

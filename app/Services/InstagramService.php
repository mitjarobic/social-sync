<?php

namespace App\Services;

use App\Models\User;
use JanuSoftware\Facebook\Facebook;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    protected Facebook $fb;
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->fb = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v22.0'
        ]);
    }

    public function getRawInstagramAccounts(): array
    {
        try {
            $token = $this->user->facebook_token;

            $pagesResponse = $this->fb->get('/me/accounts', $token);
            $pages = $pagesResponse->getDecodedBody()['data'] ?? [];

            $accounts = [];

            foreach ($pages as $page) {
                $igResponse = $this->fb->get(
                    "/{$page['id']}?fields=instagram_business_account{id,name,username,profile_picture_url}",
                    $token
                );

                $ig = $igResponse->getDecodedBody()['instagram_business_account'] ?? null;

                if ($ig) {
                    $accounts[] = $ig;
                }
            }

            return $accounts;
        } catch (\Throwable $e) {
            Log::error('Failed to fetch raw Instagram account data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Post to Instagram feed
     * Maintains same return structure as FacebookService
     */
    public function post(string $instagramId, string $pageToken, string $caption, ?string $imageUrl = null): array
    {
        if (!$imageUrl) {
            throw new \Exception("Instagram requires an image for posts");
        }

        try {
            // Create media container
            $creationResponse = $this->fb->post("/{$instagramId}/media", [
                'caption' => $caption,
                'image_url' => $imageUrl,
            ], $pageToken);

            $creationId = $creationResponse->getDecodedBody()['id'];

            // Publish the container
            $publishResponse = $this->fb->post("/{$instagramId}/media_publish", [
                'creation_id' => $creationId
            ], $pageToken);

            $postData = $publishResponse->getDecodedBody();
            $postId = $postData['id'] ?? null;

            return [
                'response' => $postData,
                'url' => $postId ? "https://www.instagram.com/p/{$postId}/" : null,
            ];
        } catch (\Throwable $e) {
            throw new \Exception("Failed to post to Instagram: " . $e->getMessage());
        }
    }

    /**
     * Get metrics for an Instagram post
     *
     * @param string $instagramId The Instagram business account ID
     * @param string $postId The Instagram post ID
     * @param string $pageToken The page access token
     * @return array The metrics data
     */
    public function getMetrics(string $instagramId, string $postId, string $pageToken): array
    {
        try {
            $reach = 0;
            $likes = 0;
            $comments = 0;

            // Try to get media insights for reach
            try {
                $insightsResponse = $this->fb->get(
                    "/{$postId}/insights?metric=reach",
                    $pageToken
                );

                $insightsData = $insightsResponse->getDecodedBody()['data'] ?? [];

                // Get reach from insights
                foreach ($insightsData as $insight) {
                    if ($insight['name'] === 'reach') {
                        $reach = $insight['values'][0]['value'] ?? 0;
                        break;
                    }
                }
            } catch (\Throwable $insightsError) {
                // Log the insights error but continue to get other metrics
                Log::warning('Failed to get Instagram post insights: ' . $insightsError->getMessage(), [
                    'instagram_id' => $instagramId,
                    'post_id' => $postId,
                ]);
            }

            // Try to get comments and likes data
            try {
                $mediaResponse = $this->fb->get(
                    "/{$postId}?fields=comments_count,like_count",
                    $pageToken
                );

                $mediaData = $mediaResponse->getDecodedBody();
                $likes = $mediaData['like_count'] ?? 0;
                $comments = $mediaData['comments_count'] ?? 0;
            } catch (\Throwable $mediaError) {
                // Log the media error but continue with zeros
                Log::warning('Failed to get Instagram post media data: ' . $mediaError->getMessage(), [
                    'instagram_id' => $instagramId,
                    'post_id' => $postId,
                ]);
            }

            Log::info('Retrieved Instagram metrics', [
                'post_id' => $postId,
                'metrics' => compact('reach', 'likes', 'comments')
            ]);

            return [
                'reach' => $reach,
                'likes' => $likes,
                'comments' => $comments,
                'shares' => 0, // Instagram doesn't have shares
            ];
        } catch (\Throwable $e) {
            // Log the error but return empty metrics
            Log::error('Failed to get Instagram metrics: ' . $e->getMessage(), [
                'instagram_id' => $instagramId,
                'post_id' => $postId,
                'exception' => $e
            ]);

            return [
                'reach' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
            ];
        }
    }

    // Removed unused findMetricValue method
}

<?php

namespace App\Services;

use App\Models\User;
use JanuSoftware\Facebook\Facebook;
use Illuminate\Support\Facades\Log;

class FacebookService
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

    public function getRedirectLoginHelper()
    {
        return $this->fb->getRedirectLoginHelper();
    }

    public function isFacebookTokenValid(): bool
    {
        $token = $this->user->facebook_token;

        if (! $token) return false;

        try {
            $response = $this->fb->get('/me?fields=id', $token);
            return isset($response->getDecodedBody()['id']);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::warning('Facebook token validation failed: ' . $e->getMessage());
            $this->user->update(['facebook_token' => null]);

            return false;
        }
    }

    public function exchangeForLongLivedToken(string $shortLivedToken): string
    {
        $oauthClient = $this->fb->getOAuth2Client();

        try {
            $longLived = $oauthClient->getLongLivedAccessToken($shortLivedToken);
            return (string) $longLived;
        } catch (\Throwable $e) {
            throw new \Exception('Failed to exchange for long-lived token: ' . $e->getMessage());
        }
    }

    public function getRawPageData(): array
    {
        try {
            $response = $this->fb->get('/me/accounts?fields=id,name,picture{url},access_token', $this->user->facebook_token);
            return $response->getDecodedBody()['data'] ?? [];
        } catch (\Throwable $e) {
            Log::error('Failed to fetch raw Facebook page data: ' . $e->getMessage());
            return [];
        }
    }

    public function post(string $pageId, string $pageToken, string $caption, ?string $imageUrl = null): array
    {
        $endpoint = $imageUrl
            ? "/{$pageId}/photos"
            : "/{$pageId}/feed";

        $params = $imageUrl
            ? ['caption' => $caption, 'url' => $imageUrl]
            : ['message' => $caption];

        try {
            $response = $this->fb->post($endpoint, $params, $pageToken);
            $body = $response->getDecodedBody();

            // Try to extract post_id or id
            $postId = $body['post_id'] ?? $body['id'] ?? null;

            $url = null;

            if ($postId && str_contains($postId, '_')) {
                // Feed post (text or image)
                [$pageId, $postOnlyId] = explode('_', $postId);
                $url = "https://www.facebook.com/{$pageId}/posts/{$postOnlyId}";
            } elseif ($postId) {
                // Single photo (may need album id if you want the full link structure)
                $url = "https://www.facebook.com/photo.php?fbid={$postId}";
            }


            return [
                'response' => $body,
                'url' => $url,
            ];
        } catch (\Throwable $e) {
            throw new \Exception("Failed to post to Facebook: " . $e);
        }
    }

    //147990835064638_122215407008078985/insights?metric=post_reactions_by_type_total,post_impressions,post_clicks,post_impressions_unique',
    ///{post_id}?fields=shares,comments.summary(true),reactions.summary(true)

    /**
     * Get metrics for a Facebook post
     *
     * @param string $pageId The Facebook page ID
     * @param string $postId The Facebook post ID
     * @param string $pageToken The page access token
     * @return array The metrics data
     */
    /**
     * Delete a post from Facebook
     *
     * @param string $postId The Facebook post ID
     * @return bool Whether the deletion was successful
     */
    public function deletePost(string $postId, string $pageToken): bool
    {
        try {
            $response = $this->fb->delete("/{$postId}", [], $pageToken);
            $body = $response->getDecodedBody();

            // Facebook returns { "success": true } when deletion is successful
            $success = $body['success'] ?? false;

            if ($success) {
                Log::info('Successfully deleted Facebook post', [
                    'post_id' => $postId,
                ]);
            } else {
                Log::warning('Facebook returned non-success response for post deletion', [
                    'post_id' => $postId,
                    'response' => $body,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('Failed to delete Facebook post: ' . $e->getMessage(), [
                'post_id' => $postId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Get metrics for a Facebook post by its full ID
     *
     * @param string $postId The Facebook post ID (can be in format pageId_postId)
     * @param string $pageToken The page access token
     * @return array The metrics data
     */
    public function getPostMetrics(string $postId, string $pageToken): array
    {
        // Check if the post ID contains a page ID (format: pageId_postId)
        if (str_contains($postId, '_')) {
            [$pageId, $postOnlyId] = explode('_', $postId);
            return $this->getMetrics($pageId, $postOnlyId, $pageToken);
        }

        // If we don't have a page ID, try to get it from the user's pages
        $pages = $this->getRawPageData();
        foreach ($pages as $page) {
            try {
                return $this->getMetrics($page['id'], $postId, $page['access_token']);
            } catch (\Exception $e) {
                // Try the next page
                Log::debug('Failed to get metrics from page', [
                    'page_id' => $page['id'],
                    'post_id' => $postId,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // If we couldn't find the post on any page, return zeros
        return [
            'reach' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
        ];
    }

    /**
     * Get metrics for a Facebook post
     *
     * @param string $pageId The Facebook page ID
     * @param string $postId The Facebook post ID
     * @param string $pageToken The page access token
     * @return array The metrics data
     */
    public function getMetrics(string $pageId, string $postId, string $pageToken): array
    {
        try {
            $reach = 0;
            $likes = 0;
            $comments = 0;
            $shares = 0;

            // Step 1: Get engagement metrics using the exact endpoint you provided
            try {
                $fieldsResponse = $this->fb->get(
                    "/{$pageId}_{$postId}?fields=shares,comments.summary(true),reactions.summary(true)",
                    $pageToken
                );
                $fields = $fieldsResponse->getDecodedBody();

                $likes = $fields['reactions']['summary']['total_count'] ?? 0;
                $comments = $fields['comments']['summary']['total_count'] ?? 0;
                $shares = $fields['shares']['count'] ?? 0;

                Log::info('Retrieved Facebook engagement metrics', [
                    'page_id' => $pageId,
                    'post_id' => $postId,
                    'likes' => $likes,
                    'comments' => $comments,
                    'shares' => $shares
                ]);
            } catch (\Throwable $fieldsError) {
                // Log the fields error but continue with zeros
                Log::warning('Failed to get Facebook post fields: ' . $fieldsError->getMessage(), [
                    'page_id' => $pageId,
                    'post_id' => $postId,
                ]);
            }

            // Step 2: Get reach using the exact insights endpoint you provided
            try {
                $insightsResponse = $this->fb->get(
                    "/{$pageId}_{$postId}/insights?metric=post_reactions_by_type_total,post_impressions,post_clicks,post_impressions_unique",
                    $pageToken
                );

                $insights = $insightsResponse->getDecodedBody();

                // Look for post_impressions_unique first (true reach)
                if (isset($insights['data'])) {
                    foreach ($insights['data'] as $item) {
                        if ($item['name'] === 'post_impressions_unique') {
                            $reach = $item['values'][0]['value'] ?? 0;
                            Log::info('Found post_impressions_unique', [
                                'page_id' => $pageId,
                                'post_id' => $postId,
                                'reach' => $reach
                            ]);
                            break;
                        }
                    }

                    // If we couldn't find post_impressions_unique, try post_impressions
                    if ($reach === 0) {
                        foreach ($insights['data'] as $item) {
                            if ($item['name'] === 'post_impressions') {
                                $reach = $item['values'][0]['value'] ?? 0;
                                Log::info('Found post_impressions', [
                                    'page_id' => $pageId,
                                    'post_id' => $postId,
                                    'reach' => $reach
                                ]);
                                break;
                            }
                        }
                    }
                }
            } catch (\Throwable $insightsError) {
                Log::warning('Failed to get Facebook post insights: ' . $insightsError->getMessage(), [
                    'page_id' => $pageId,
                    'post_id' => $postId,
                ]);
            }

            // If reach is still 0, set it to 1 as you mentioned
            if ($reach === 0) {
                $reach = 1;
                Log::info('Setting reach to 1 as per requirement', [
                    'page_id' => $pageId,
                    'post_id' => $postId
                ]);
            }

            Log::info('Retrieved Facebook metrics', [
                'page_id' => $pageId,
                'post_id' => $postId,
                'metrics' => compact('reach', 'likes', 'comments', 'shares')
            ]);

            return compact('reach', 'likes', 'comments', 'shares');
        } catch (\Throwable $e) {
            Log::error('Failed to get Facebook metrics: ' . $e->getMessage(), [
                'page_id' => $pageId,
                'post_id' => $postId,
            ]);

            // Even in case of error, return reach as 1
            return [
                'reach' => 1,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
            ];
        }
    }
}

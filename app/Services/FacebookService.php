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
    public function getMetrics(string $pageId, string $postId, string $pageToken): array
    {
        // dd(compact('pageId', 'postId', 'pageToken'));
        try {
            // First: get post insights
            $insightsResponse = $this->fb->get(
                "/{$pageId}_{$postId}/insights?metric=post_impressions,post_engaged_users",
                $pageToken
            );

            dd($insightsResponse->getDecodedBody());
            $insights = $insightsResponse->getDecodedBody();

            $reach = 0;
            if (isset($insights['data'])) {
                foreach ($insights['data'] as $item) {
                    if ($item['name'] === 'post_impressions') {
                        $reach = $item['values'][0]['value'] ?? 0;
                    }
                }
            }

            // Second: get public fields (reactions, comments, shares)
            $fieldsResponse = $this->fb->get(
                "/{$postId}?fields=reactions.summary(true),comments.summary(true),shares",
                $pageToken
            );
            $fields = $fieldsResponse->getDecodedBody();

            $likes = $fields['reactions']['summary']['total_count'] ?? 0;
            $comments = $fields['comments']['summary']['total_count'] ?? 0;
            $shares = $fields['shares']['count'] ?? 0;

            // dd(compact('reach', 'likes', 'comments', 'shares'));

            return compact('reach', 'likes', 'comments', 'shares');
        } catch (\Throwable $e) {
            Log::error('Failed to get Facebook metrics: ' . $e->getMessage(), [
                'page_id' => $pageId,
                'post_id' => $postId,
            ]);

            return [
                'reach' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
            ];
        }
    }
}

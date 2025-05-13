<?php

namespace App\Services;

use Filament\Forms\Set;
use App\Support\ImageStore;
use JanuSoftware\Facebook\Facebook;
use Barryvdh\Debugbar\Facades\Debugbar;

class FacebookService
{
    protected Facebook $fb;

    public function __construct()
    {
        $this->fb = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v22.0'
        ]);
    }

    public function listPages(): array
    {
        try {
            $token = config('services.facebook.user_token'); // your long-lived user token

            $response = $this->fb->get('/me/accounts', $token);
    
            $pages = $response->getDecodedBody()['data'] ?? [];

            return collect($pages)->mapWithKeys(fn($page) => [$page['id'] => $page['name']])->all();

        } catch (\Throwable $e) {
            throw new \Exception("Failed to fetch Facebook Pages: " . $e->getMessage());
        }
    }

    public function fillPageDetails(string $pageId, Set $set): void
    {
        $token = config('services.facebook.user_token');
        $response = $this->fb->get('/me/accounts?fields=id,name,picture{url},access_token', $token);

        $pages = $response->getDecodedBody()['data'] ?? [];

        $page = collect($pages)->firstWhere('id', $pageId);

        if ($page) {
            $set('label', $page['name']);
            $set('external_name', $page['name']);
            $set('external_url', "https://facebook.com/{$page['id']}");
            $imageUrl = ImageStore::savePlatformPhoto('instagram', $pageId, $page['picture']['data']['url'] );
            $set('external_picture_url', $imageUrl);
            $set('external_token', $page['access_token'] ?? null);
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
}

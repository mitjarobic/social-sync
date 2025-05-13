<?php

namespace App\Services;

use App\Support\ImageStore;
use Filament\Forms\Set;
use JanuSoftware\Facebook\Facebook;

class InstagramService
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

    /**
     * List all Instagram business accounts connected to accessible Facebook pages
     * Returns array of [id => name] like FacebookService
     */
    public function listAccounts(): array
    {
        try {

            $token = config('services.facebook.user_token'); // your long-lived user token

            // First get all Facebook pages
            $pagesResponse = $this->fb->get('/me/accounts', $token);

            $pages = $pagesResponse->getDecodedBody()['data'] ?? [];

            return collect($pages)
                ->mapWithKeys(function ($page) use ($token) {
                    $igResponse = $this->fb->get(
                        "/{$page['id']}?fields=instagram_business_account{id,name,username}",
                        $token
                    );

                    $igAccount = $igResponse->getDecodedBody()['instagram_business_account'] ?? null;

                    return $igAccount
                        ? [$igAccount['id'] => $igAccount['name'] ?? $igAccount['username']]
                        : [];
                })
                ->filter()
                ->all();
        } catch (\Throwable $e) {
            throw new \Exception("Failed to fetch Instagram accounts: " . $e->getMessage());
        }
    }

    /**
     * Fill details for a specific Instagram account
     * Mirrors FacebookService's fillPageDetails pattern
     */
    public function fillAccountDetails(string $instagramId, Set $set): void
    {
        try {

            $token = config('services.facebook.user_token'); // your long-lived user token

            $response = $this->fb->get(
                "/{$instagramId}?fields=id,name,username,profile_picture_url",
                $token
            );

            $account = $response->getDecodedBody();

            if ($account) {
                $set('label', $account['name']);
                $set('external_name', $account['name']);
                $set('external_url', "https://instagram.com/{$account['username']}");
                $imageUrl = ImageStore::savePlatformPhoto('instagram', $instagramId, $account['profile_picture_url']);
                $set('external_picture_url', $imageUrl);
             
            }
        } catch (\Throwable $e) {
            throw new \Exception("Failed to fetch Instagram account details: " . $e->getMessage());
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
}

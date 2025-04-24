<?php

namespace App\Services;

use Facebook\Facebook;

class InstagramService
{
    protected Facebook $fb;

    public function __construct()
    {
        $this->fb = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v18.0',
            'default_access_token' => config('services.facebook.token'), // Page token
        ]);
    }

    public function post(string $imageUrl, string $caption): array
    {
        // Step 1: Get the Facebook Page ID
        $pageResponse = $this->fb->get('/me/accounts');
        $pageId = data_get($pageResponse->getDecodedBody(), 'data.0.id');

        // Step 2: Get the Instagram Business Account ID
        $igResponse = $this->fb->get("/{$pageId}?fields=instagram_business_account");
        $instagramId = data_get($igResponse->getDecodedBody(), 'instagram_business_account.id');

        if (!$instagramId) {
            throw new \Exception('Instagram business account not found.');
        }

        // Step 3: Create the image container
        $container = $this->fb->post("/{$instagramId}/media", [
            'image_url' => $imageUrl,
            'caption' => $caption,
        ])->getDecodedBody();

        $creationId = $container['id'] ?? null;

        if (!$creationId) {
            throw new \Exception('Failed to create Instagram media container.');
        }

        // Step 4: Publish the container
        $publish = $this->fb->post("/{$instagramId}/media_publish", [
            'creation_id' => $creationId,
        ])->getDecodedBody();

        return $publish;
    }
}

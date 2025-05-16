<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\Platform;
use App\Support\ImageStore;

class PlatformSyncService
{
    protected FacebookService $facebook;
    protected InstagramService $instagram;

    public function __construct(protected User $user)
    {
        $this->user = $user;
        $this->facebook = new FacebookService($user);
        $this->instagram = new InstagramService($user);
    }

    /**
     * Sync Facebook and Instagram platforms
     */
    public function syncPlatforms(): void
    {
        // ✅ Facebook Pages
        foreach ($this->facebook->getRawPageData() as $page) {
            $imageUrl = ImageStore::savePlatformPhoto('facebook', $page['id'], $page['picture']['data']['url']);
            Platform::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'provider' => 'facebook',
                    'external_id' => $page['id'],
                ],
                [
                    'label' => $page['name'],
                    'external_name' => $page['name'],
                    'external_url' => "https://facebook.com/{$page['id']}",
                    'external_token' => $page['access_token'] ?? null,
                    'external_picture_url' => $imageUrl,
                ]
            );
        }

        // ✅ Instagram Accounts
        foreach ($this->instagram->getRawInstagramAccounts() as $ig) {
            $imageUrl = ImageStore::savePlatformPhoto('instagram', $ig['id'], $ig['profile_picture_url']);

            Platform::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'provider' => 'instagram',
                    'external_id' => $ig['id'],
                ],
                [
                    'label' => $ig['name'],
                    'external_name' => $ig['name'],
                    'external_url' => "https://instagram.com/{$ig['username']}",
                    'external_token' => null, // IG uses FB page token
                    'external_picture_url' => $imageUrl
                ]
            );
        }
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Models\Platform;
use App\Support\ImageStore;
use Illuminate\Support\Arr;

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

            $imageUrl = Arr::has($page, 'picture.data.url') ?
                ImageStore::savePlatformPhoto('facebook', $page['id'], $page['picture']['data']['url']) : null;

            Platform::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'company_id' => $this->user->currentCompany->id,
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
            $imageUrl = Arr::has($ig, 'profile_picture_url') ? 
                ImageStore::savePlatformPhoto('instagram', $ig['id'], $ig['profile_picture_url']) : null;

            Platform::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'company_id' => $this->user->currentCompany->id,
                    'provider' => 'instagram',
                    'external_id' => $ig['id'],
                ],
                [
                    'label' => $ig['name'],
                    'external_name' => $ig['name'],
                    'external_url' => "https://instagram.com/{$ig['username']}",
                    'external_token' => null, // Instagram uses the user's Facebook token, not stored here
                    'external_picture_url' => $imageUrl
                ]
            );
        }
    }
}

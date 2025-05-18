<?php

namespace App\Services;

use App\Models\PlatformPost;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PlatformPostDeletionService
{

    /**
     * Delete a platform post both from the platform and the database
     *
     * @param PlatformPost $platformPost The platform post to delete
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(PlatformPost $platformPost): array
    {
        // Get the platform provider
        $platform = $platformPost->platform;

        if (!$platform) {
            // If there's no platform, just delete the record
            $platformPost->delete();
            return [
                'success' => true,
                'message' => 'Post deleted from database (no platform found)'
            ];
        }

        $provider = $platform->provider;
        $success = false;

        // Only proceed with platform deletion if we have an external ID
        if (!$platformPost->external_id) {
            // If there's no external ID, just delete the record
            $platformPost->delete();
            return [
                'success' => true,
                'message' => 'Post deleted from database (no external ID)'
            ];
        }

        // Special handling for Instagram posts
        if ($provider === 'instagram') {
            // Instagram API doesn't support post deletion, so we can only delete from our database
            $platformPost->delete();

            // Update the parent post status
            if ($platformPost->post) {
                $platformPost->post->updateStatus();
            }

            return [
                'success' => true,
                'message' => 'Post removed from database. Note: Instagram posts cannot be deleted through the API and must be manually deleted from Instagram.'
            ];
        }

        // For other platforms, try to delete from the platform
        switch ($provider) {
            case 'facebook':
                $success = $this->deleteFacebookPost($platformPost);
                break;

            case 'x':
                $success = $this->deleteXPost($platformPost);
                break;

            default:
                $success = false;
                break;
        }

        if ($success) {
            // If successfully deleted from the platform, delete from database
            $platformPost->delete();

            // Update the parent post status
            if ($platformPost->post) {
                $platformPost->post->updateStatus();
            }

            return [
                'success' => true,
                'message' => 'Post deleted successfully from ' . ucfirst($provider)
            ];
        } else {
            // Log the error
            Log::error('Failed to delete platform post', [
                'platform' => $provider,
                'external_id' => $platformPost->external_id,
                'platform_post_id' => $platformPost->id
            ]);

            // NEVER delete the local record if we failed to delete from the platform
            return [
                'success' => false,
                'message' => 'Failed to delete post from ' . ucfirst($provider) . '. Please try again or delete manually.'
            ];
        }
    }

    /**
     * Delete a Facebook post
     *
     * @param PlatformPost $platformPost
     * @return bool
     */
    protected function deleteFacebookPost(PlatformPost $platformPost): bool
    {
        $service = new FacebookService(Auth::user());
        return $service->deletePost(
            $platformPost->external_id,
            $platformPost->platform->external_token
        );
    }



    /**
     * Delete an X (Twitter) post
     *
     * @param PlatformPost $platformPost
     * @return bool
     */
    protected function deleteXPost(PlatformPost $platformPost): bool
    {
        // X (Twitter) deletion would go here
        // $service = new XService(Auth::user());
        // return $service->deletePost($platformPost->external_id);

        // Not implemented yet
        return false;
    }
}

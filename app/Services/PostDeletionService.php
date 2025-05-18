<?php

namespace App\Services;

use App\Models\Post;
use App\Support\ImageStore;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PostDeletionService
{
    /**
     * Delete a post and handle related records
     *
     * @param Post $post The post to delete
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(Post $post): array
    {
        try {
            // Start a database transaction
            DB::beginTransaction();
            
            // Get all platform posts associated with this post
            $platformPosts = $post->platformPosts;
            
            // Delete each platform post using the service
            $deletionService = new PlatformPostDeletionService();
            $allSuccess = true;
            $failedPlatforms = [];
            
            foreach ($platformPosts as $platformPost) {
                $result = $deletionService->delete($platformPost);
                
                if (!$result['success']) {
                    $allSuccess = false;
                    $failedPlatforms[] = $platformPost->platform->provider ?? 'unknown';
                }
            }
            
            // If all platform posts were deleted successfully, delete the post
            if ($allSuccess) {
                // Delete the post's image if it exists
                if ($post->image_path && ImageStore::exists($post->image_path)) {
                    ImageStore::delete($post->image_path);
                }
                
                // Delete the post's background image if it exists
                if ($post->image_bg_image_path && ImageStore::exists($post->image_bg_image_path)) {
                    ImageStore::delete($post->image_bg_image_path);
                }
                
                // Delete the post
                $post->delete();
                
                // Commit the transaction
                DB::commit();
                
                return [
                    'success' => true,
                    'message' => "Post deleted successfully from all platforms."
                ];
            } else {
                // If any platform post deletion failed, rollback and show error
                DB::rollBack();
                $failedPlatformsStr = implode(', ', $failedPlatforms);
                
                return [
                    'success' => false,
                    'message' => "Failed to delete post from some platforms: {$failedPlatformsStr}"
                ];
            }
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            
            // Log the error
            Log::error('Error deleting post', [
                'post_id' => $post->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => "Failed to delete post: {$e->getMessage()}"
            ];
        }
    }
}

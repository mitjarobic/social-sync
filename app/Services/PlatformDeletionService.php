<?php

namespace App\Services;

use App\Models\Platform;
use App\Support\ImageStore;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PlatformDeletionService
{
    /**
     * Delete a platform and handle related records
     *
     * @param Platform $platform The platform to delete
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(Platform $platform): array
    {
        try {
            // Start a database transaction
            DB::beginTransaction();

            // Check if there are any platform posts associated with this platform
            $platformPostsCount = $platform->platformPosts()->count();

            if ($platformPostsCount > 0) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Cannot remove platform '{$platform->label}' because it has {$platformPostsCount} associated " .
                        ($platformPostsCount === 1 ? 'post' : 'posts') . ". Please remove the " .
                        ($platformPostsCount === 1 ? 'post' : 'posts') . " first."
                ];
            }

            // Delete the platform's picture if it exists
            if ($platform->external_picture_url && ImageStore::exists($platform->external_picture_url)) {
                ImageStore::delete($platform->external_picture_url);
            }

            // Delete the platform
            $platform->delete();

            // Commit the transaction
            DB::commit();

            return [
                'success' => true,
                'message' => "Platform '{$platform->label}' removed successfully."
            ];
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            // Log the error
            Log::error('Error deleting platform', [
                'platform_id' => $platform->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => "Failed to remove platform: {$e->getMessage()}"
            ];
        }
    }
}

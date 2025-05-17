<?php

namespace App\Filament\Company\Resources\PostResource\Actions;

use App\Models\Post;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PlatformPostDeletionService;

class DeletePostAction extends DeleteAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Delete')
            ->modalHeading('Delete post')
            ->modalDescription('Are you sure you want to delete this post? This will remove it from all platforms and cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete post')
            ->action(function (Model|Post $record) {
                try {
                    // Start a database transaction
                    DB::beginTransaction();
                    
                    // Get all platform posts associated with this post
                    $platformPosts = $record->platformPosts;
                    
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
                        $record->delete();
                        DB::commit();
                        $this->success('Post deleted successfully from all platforms');
                    } else {
                        // If any platform post deletion failed, rollback and show error
                        DB::rollBack();
                        $failedPlatformsStr = implode(', ', $failedPlatforms);
                        $this->failure("Failed to delete post from some platforms: {$failedPlatformsStr}");
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error deleting post', [
                        'post_id' => $record->id,
                        'error' => $e->getMessage()
                    ]);
                    $this->failure('An error occurred while deleting the post: ' . $e->getMessage());
                }
            });
    }
}

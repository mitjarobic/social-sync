<?php

namespace App\Jobs;

use App\Enums\PostStatus;
use App\Enums\PlatformPostStatus;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPlatformPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $postId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {

            $post = Post::findOrFail($this->postId);

            // First, update the post status to PUBLISHING
            $post->status = PostStatus::PUBLISHING;
            $post->save();

            // Dispatch PublishPlatformPost job for each platform post
            $post->platformPosts()
                ->where('status', PlatformPostStatus::QUEUED)
                ->each(function ($platformPost) {
                    // Dispatch the PublishPlatformPost job
                    PublishPlatformPost::dispatch($platformPost->id);
                });

            // Update the post status again after dispatching all jobs
            $post->updateStatus();
            
            Log::info('Platform posts publishing jobs dispatched', [
                'post_id' => $post->id,
                'platform_posts_count' => $post->platformPosts()->where('status', PlatformPostStatus::QUEUED)->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error dispatching platform posts publishing jobs', [
                'post_id' => $post->id,
                'error' => $e->getMessage()
            ]);
            
            // Update post status to FAILED
            $post->status = PostStatus::FAILED;
            $post->save();
            
            throw $e;
        }
    }
}

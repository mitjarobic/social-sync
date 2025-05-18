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
    public function __construct(public Post $post)
    {
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // First, update the post status to PUBLISHING
            $this->post->status = PostStatus::PUBLISHING;
            $this->post->save();

            // Dispatch PublishPlatformPost job for each platform post
            $this->post->platformPosts()
                ->where('status', PlatformPostStatus::QUEUED)
                ->each(function ($platformPost) {
                    // Dispatch the PublishPlatformPost job
                    PublishPlatformPost::dispatch($platformPost);
                });

            // Update the post status again after dispatching all jobs
            $this->post->updateStatus();
            
            Log::info('Platform posts publishing jobs dispatched', [
                'post_id' => $this->post->id,
                'platform_posts_count' => $this->post->platformPosts()->where('status', PlatformPostStatus::QUEUED)->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error dispatching platform posts publishing jobs', [
                'post_id' => $this->post->id,
                'error' => $e->getMessage()
            ]);
            
            // Update post status to FAILED
            $this->post->status = PostStatus::FAILED;
            $this->post->save();
            
            throw $e;
        }
    }
}

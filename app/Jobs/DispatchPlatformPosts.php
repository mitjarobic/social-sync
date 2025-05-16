<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchPlatformPosts implements ShouldQueue
{
    use Queueable;

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
        // First, update the post status to PUBLISHING
        $this->post->status = \App\Enums\PostStatus::PUBLISHING;
        $this->post->save();

        // Dispatch jobs for each platform post
        $this->post->platformPosts()
            ->where('status', \App\Enums\PlatformPostStatus::QUEUED)
            ->each(function ($platformPost) {
                // Update platform post status to PUBLISHING
                $platformPost->status = \App\Enums\PlatformPostStatus::PUBLISHING;
                $platformPost->save();

                // Dispatch the appropriate job based on platform
                match ($platformPost->platform->provider) {
                    'instagram' => \App\Jobs\PostToInstagram::dispatch($platformPost),
                    'facebook' => \App\Jobs\PostToFacebook::dispatch($platformPost),
                    'x' => \App\Jobs\PostToX::dispatch($platformPost),
                    default => null
                };
            });

        // Update the post status again after dispatching all jobs
        $this->post->updateStatus();
    }
}

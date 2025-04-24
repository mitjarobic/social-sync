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

    // app/Jobs/DispatchPlatformPosts.php
    public function handle()
    {
        $this->post->platformPosts()
            ->where('status', \App\Enums\PlatformPostStatus::QUEUED)
            ->each(function ($platformPost) {
                match ($platformPost->platform->provider) {
                    'instagram' => \App\Jobs\PostToInstagram::dispatch($platformPost),
                    'facebook' => \App\Jobs\PostToFacebook::dispatch($platformPost),
                    'x' => \App\Jobs\PostToX::dispatch($platformPost),
                    default => null
                };
            });
    }
}

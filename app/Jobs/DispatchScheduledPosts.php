<?php

namespace App\Jobs;

use App\Models\Post;
use App\Enums\PlatformPostStatus;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchScheduledPosts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Post::whereHas(
            'platformPosts',
            fn($q) =>
            $q->where('status', PlatformPostStatus::QUEUED)
                ->where('scheduled_at', '<=', now())
        )->each(function ($post) {
            DispatchPlatformPosts::dispatch($post);
        });
    }
}

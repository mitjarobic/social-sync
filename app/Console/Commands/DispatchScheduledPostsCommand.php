<?php

namespace App\Console\Commands;

use App\Jobs\DispatchPlatformPosts;
use App\Models\Post;
use App\Enums\PlatformPostStatus;
use Illuminate\Console\Command;

class DispatchScheduledPostsCommand extends Command
{
    protected $signature = 'posts:dispatch-scheduled';
    protected $description = 'Dispatch all scheduled posts that are due for publishing';

    public function handle()
    {
        $posts = Post::whereHas(
            'platformPosts',
            fn($q) => $q->where('status', PlatformPostStatus::QUEUED)
                ->where('scheduled_at', '<=', now())
        )->get();

        $count = $posts->count();
        
        if ($count === 0) {
            $this->info("No scheduled posts to dispatch.");
            return;
        }
        
        $this->info("Found {$count} scheduled posts to dispatch.");
        
        foreach ($posts as $post) {
            DispatchPlatformPosts::dispatch($post);
            $this->line("Dispatched post #{$post->id}");
        }
        
        $this->info("All scheduled posts have been dispatched.");
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use App\Enums\PlatformPostStatus;
use App\Jobs\PublishPlatformPosts;

class ProcessScheduledPostCommand extends Command
{
    protected $signature = 'posts:process-scheduled';
    protected $description = 'Process all scheduled posts that are due for publishing (uses UTC for date comparison)';

    public function handle()
    {
        // Use UTC for scheduled post processing
        $now = now()->setTimezone('UTC');

        $posts = Post::whereHas(
            'platformPosts',
            fn($q) => $q->where('status', PlatformPostStatus::QUEUED)
                ->where('scheduled_at', '<=', $now)
        )->get();

        $count = $posts->count();

        if ($count === 0) {
            $this->info("No scheduled posts to process.");
            return;
        }

        $this->info("Found {$count} scheduled posts to process.");

        foreach ($posts as $post) {
            // Directly dispatch PublishPlatformPosts job
            PublishPlatformPosts::dispatch($post);
            $this->line("Processing post #{$post->id}");
        }

        $this->info("All scheduled posts have been processed.");
    }
}

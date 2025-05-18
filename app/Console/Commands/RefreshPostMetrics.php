<?php

namespace App\Console\Commands;

use App\Models\PlatformPost;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshPostMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:refresh-metrics {--platform= : Filter by platform (facebook, instagram, x)} {--post-id= : Specific platform post ID} {--days= : Only update posts from the last N days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh metrics for published posts';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $platform = $this->option('platform');
        $postId = $this->option('post-id');

        $this->info('Refreshing post metrics...');

        // Build the query
        $query = PlatformPost::query()
            ->where('status', 'published')
            ->whereNotNull('external_id');

        // Filter by platform if specified
        if ($platform) {
            $query->whereHas('platform', function ($q) use ($platform) {
                $q->where('provider', $platform);
            });
        }

        // Filter by post ID if specified
        if ($postId) {
            $query->where('id', $postId);
        }

        // Filter by days if specified
        if ($this->option('days')) {
            $days = (int) $this->option('days');
            $cutoffDate = now()->subDays($days);
            $query->where('posted_at', '>=', $cutoffDate);
            $this->info("Filtering posts from the last {$days} days.");
        }

        // Get the posts with necessary relationships
        $posts = $query->with(['platform', 'user', 'company.user'])->get();

        $this->info("Found {$posts->count()} posts to refresh.");

        $successCount = 0;
        $failCount = 0;

        foreach ($posts as $post) {
            $this->info("Refreshing metrics for post {$post->id} ({$post->platform->provider})...");

            try {
                // Instead of trying to update metrics directly, dispatch the job
                // This ensures consistent behavior and token handling
                \App\Jobs\UpdatePlatformPostMetrics::dispatchSync($post);

                $this->info("Successfully refreshed metrics for post {$post->id}.");
                $successCount++;
            } catch (\Exception $e) {
                $this->error("Error refreshing metrics for post {$post->id}: {$e->getMessage()}");
                Log::error("Error refreshing metrics for post {$post->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failCount++;
            }
        }

        $this->info("Metrics refresh completed. Success: {$successCount}, Failed: {$failCount}");
        return Command::SUCCESS;
    }
}

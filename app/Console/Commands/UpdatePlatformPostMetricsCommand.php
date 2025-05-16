<?php

namespace App\Console\Commands;

use App\Jobs\UpdatePlatformPostMetrics;
use App\Models\PlatformPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdatePlatformPostMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform-post:update-metrics {--id=} {--all} {--scheduled} {--published}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update metrics for platform posts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting platform post metrics update...');
        
        // Get the posts to update based on the options
        $posts = $this->getPostsToUpdate();
        
        if ($posts->isEmpty()) {
            $this->warn('No posts found to update metrics for.');
            return 0;
        }
        
        $this->info("Found {$posts->count()} posts to update metrics for.");
        
        // Process each post
        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();
        
        foreach ($posts as $post) {
            try {
                // Dispatch the job to update metrics
                UpdatePlatformPostMetrics::dispatch($post);
                $bar->advance();
            } catch (\Exception $e) {
                Log::error('Failed to update metrics for platform post', [
                    'platform_post_id' => $post->id,
                    'error' => $e->getMessage()
                ]);
                $this->error("Error updating metrics for post ID {$post->id}: {$e->getMessage()}");
            }
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Platform post metrics update completed.');
        
        return 0;
    }
    
    /**
     * Get the posts to update based on the command options
     */
    private function getPostsToUpdate()
    {
        // If a specific ID is provided, get that post
        if ($this->option('id')) {
            return PlatformPost::where('id', $this->option('id'))->get();
        }
        
        // Start with a base query
        $query = PlatformPost::query();
        
        // Filter by status if specified
        if ($this->option('scheduled')) {
            $query->where('status', 'scheduled');
        } elseif ($this->option('published')) {
            $query->where('status', 'published');
        } elseif (!$this->option('all')) {
            // Default to published posts if no specific option is provided
            $query->where('status', 'published');
        }
        
        return $query->get();
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\PlatformPostStatus;
use App\Jobs\UpdatePlatformPostMetrics as UpdatePlatformPostMetricsJob;
use App\Models\PlatformPost;
use Illuminate\Console\Command;

class UpdatePlatformPostMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform-posts:update-metrics {--days=7 : Only update posts from the last N days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update metrics for all published platform posts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        
        $this->info("Updating metrics for platform posts from the last {$days} days...");
        
        $cutoffDate = now()->subDays($days);
        
        $query = PlatformPost::query()
            ->where('status', PlatformPostStatus::PUBLISHED)
            ->whereNotNull('external_id')
            ->where('posted_at', '>=', $cutoffDate);
            
        $count = $query->count();
        
        if ($count === 0) {
            $this->info("No platform posts found to update.");
            return;
        }
        
        $this->info("Found {$count} platform posts to update.");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        $query->each(function (PlatformPost $platformPost) use ($bar) {
            UpdatePlatformPostMetricsJob::dispatch($platformPost);
            $bar->advance();
        });
        
        $bar->finish();
        $this->newLine();
        $this->info("All platform post metrics update jobs have been dispatched.");
    }
}

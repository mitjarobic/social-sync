<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process scheduled posts every minute
        $schedule->command('posts:process-scheduled')->everyMinute();

        // Update metrics for platform posts every hour
        $schedule->command('posts:refresh-metrics')->hourly();

        // Sync platforms (Facebook pages and Instagram accounts) daily
        $schedule->command('platforms:sync --all')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

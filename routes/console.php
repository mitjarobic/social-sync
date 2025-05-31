<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// We now use the command 'posts:process-scheduled' instead of scheduling the job directly
// See app/Console/Kernel.php for the scheduled command

// Sync platforms for a specific user
Artisan::command('platforms:sync-user {userId}', function ($userId) {
    $this->info("Manually syncing platforms for user ID: {$userId}");
    Artisan::call('platforms:sync', ['--user' => $userId]);
    $this->info("Sync completed");
})->purpose('Sync platforms for a specific user');


// Process scheduled posts every minute
Schedule::command('posts:process-scheduled')->everyMinute();

        // Update metrics for platform posts every hour
Schedule::command('posts:refresh-metrics')->hourly();

        // Sync platforms (Facebook pages and Instagram accounts) daily
Schedule::command('platforms:sync --all')->daily();


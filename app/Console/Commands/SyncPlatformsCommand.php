<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PlatformSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPlatformsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platforms:sync {--user=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync platforms (Facebook pages and Instagram accounts) for users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->syncForAllUsers();
        } elseif ($userId = $this->option('user')) {
            $this->syncForUser($userId);
        } else {
            $this->error('Please specify either --user=ID or --all option');
            return 1;
        }

        return 0;
    }

    /**
     * Sync platforms for a specific user
     *
     * @param int $userId
     * @return void
     */
    protected function syncForUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return;
        }

        $this->info("Syncing platforms for user: {$user->name} (ID: {$user->id})");
        
        try {
            $syncService = new PlatformSyncService($user);
            $syncService->syncPlatforms();
            $this->info("✅ Successfully synced platforms for user {$user->name}");
        } catch (\Exception $e) {
            $this->error("Failed to sync platforms for user {$user->name}: {$e->getMessage()}");
            Log::error("Platform sync failed for user {$user->id}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Sync platforms for all users with Facebook tokens
     *
     * @return void
     */
    protected function syncForAllUsers()
    {
        $users = User::whereNotNull('facebook_token')->get();
        
        if ($users->isEmpty()) {
            $this->warn("No users with Facebook tokens found");
            return;
        }

        $this->info("Syncing platforms for {$users->count()} users with Facebook tokens");
        
        $successCount = 0;
        $failCount = 0;

        foreach ($users as $user) {
            $this->line("Processing user: {$user->name} (ID: {$user->id})");
            
            try {
                $syncService = new PlatformSyncService($user);
                $syncService->syncPlatforms();
                $this->line("✅ Successfully synced platforms for user {$user->name}");
                $successCount++;
            } catch (\Exception $e) {
                $this->error("Failed to sync platforms for user {$user->name}: {$e->getMessage()}");
                Log::error("Platform sync failed for user {$user->id}", [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failCount++;
            }
        }

        $this->info("Sync completed: {$successCount} successful, {$failCount} failed");
    }
}

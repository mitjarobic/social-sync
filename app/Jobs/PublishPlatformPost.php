<?php

namespace App\Jobs;

use App\Enums\PlatformPostStatus;
use App\Models\PlatformPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPlatformPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param $platformPostId
     * @return void
     */

    public PlatformPost $platformPost;

    public function __construct(public int $platformPostId)
    {
        $this->platformPost = PlatformPost::findOrFail($this->platformPostId);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Skip if the platform post is already published
        if ($this->platformPost->status === PlatformPostStatus::PUBLISHED) {
            return;
        }

        // Update status to publishing
        $this->platformPost->update([
            'status' => PlatformPostStatus::PUBLISHING,
        ]);

        try {
            // Get the platform provider
            $platform = $this->platformPost->platform;
            $provider = $platform->provider;
            $success = false;

            // Dispatch the appropriate platform-specific job
            $success = false;

            try {
                // Use the existing platform-specific jobs
                // When QUEUE_CONNECTION=sync, these will run synchronously
                match ($provider) {
                    'facebook' => \App\Jobs\PostToFacebook::dispatch($this->platformPost->id),
                    'instagram' => \App\Jobs\PostToInstagram::dispatch($this->platformPost->id),
                    'x' => \App\Jobs\PostToX::dispatch($this->platformPost->id),
                    default => null
                };

                // Refresh the platform post to get the updated status
                $this->platformPost->refresh();

                // Check if the post was published successfully
                $success = $this->platformPost->status === PlatformPostStatus::PUBLISHED;
            } catch (\Exception $e) {
                Log::error('Error in platform-specific publishing job', [
                    'platform_post_id' => $this->platformPost->id,
                    'platform' => $provider,
                    'error' => $e->getMessage()
                ]);
                $success = false;
            }

            // If publishing failed, update status to failed
            if (!$success) {
                $this->platformPost->update([
                    'status' => PlatformPostStatus::FAILED,
                ]);

                Log::error('Failed to publish platform post', [
                    'platform_post_id' => $this->platformPost->id,
                    'platform' => $provider,
                ]);

                // Throw an exception to trigger job failure
                throw new \Exception("Failed to publish to {$provider}");
            }

            // Update the parent post status
            if ($this->platformPost->post) {
                $this->platformPost->post->updateStatus();
            }
        } catch (\Exception $e) {
            // Update status to failed
            $this->platformPost->update([
                'status' => PlatformPostStatus::FAILED,
            ]);

            Log::error('Error publishing platform post', [
                'platform_post_id' => $this->platformPost->id,
                'error' => $e->getMessage(),
            ]);

            // Rethrow the exception to trigger job failure
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        // Update status to failed
        $this->platformPost->update([
            'status' => PlatformPostStatus::FAILED,
        ]);

        Log::error('Platform post publishing job failed', [
            'platform_post_id' => $this->platformPost->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Support\DevHelper;
use App\Support\ImageStore;
use App\Models\PlatformPost;
use App\Services\InstagramService;
use App\Enums\PlatformPostStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostToInstagram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PlatformPost $platformPost) {}

    public function handle(InstagramService $service)
    {
        try {
            $imageUrl = DevHelper::withNgrokUrl(ImageStore::url($this->platformPost->post->image_path));

            // Get the user's Facebook token since Instagram uses the same token
            $token = $this->platformPost->user->facebook_token;

            if (!$token) {
                throw new \Exception("No Facebook token found for user. Instagram posting requires a valid Facebook token.");
            }

            $result = $service->post(
                $this->platformPost->platform->external_id,
                $token, // Use the user's Facebook token
                $this->platformPost->post->content,
                $imageUrl
            );

            $this->platformPost->update([
                'status' => PlatformPostStatus::PUBLISHED,
                'external_id' => $result['response']['id'],
                'external_url' => $result['url'],
                'posted_at' => now(),
                'metadata' => $result['response'],
                'reach' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'metrics_updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Ensure the status is updated to FAILED with detailed error information
            $errorMessage = $e->getMessage();
            $errorDetails = [
                'error' => $errorMessage,
                'time' => now()->toDateTimeString(),
                'type' => 'instagram_posting_error'
            ];

            // Check for specific permission errors
            if (strpos($errorMessage, 'Application does not have permission') !== false) {
                $errorDetails['solution'] = 'Check that your Facebook app has the necessary permissions: instagram_basic, instagram_content_publish, and pages_read_engagement';
            } elseif (strpos($errorMessage, 'token') !== false) {
                $errorDetails['solution'] = 'Check that the user has a valid Facebook token';
            }

            $this->platformPost->update([
                'status' => PlatformPostStatus::FAILED,
                'metadata' => $errorDetails
            ]);

            // Log the error with detailed information
            Log::error('Failed to post to Instagram', [
                'platform_post_id' => $this->platformPost->id,
                'platform_id' => $this->platformPost->platform->id,
                'external_id' => $this->platformPost->platform->external_id,
                'user_id' => $this->platformPost->user->id ?? null,
                'facebook_token_exists' => !empty($token),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Rethrow the exception to ensure the parent job knows it failed
            throw $e;
        }

        $this->updateParentPostStatus();
    }

    protected function updateParentPostStatus()
    {
        $this->platformPost->post->refresh()->updateStatus();
    }
}

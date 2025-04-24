<?php

namespace App\Jobs;

use App\Services\XService;
use App\Models\PlatformPost;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostToX implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PlatformPost $platformPost) {}

    public function handle(XService $service)
    {
        try {
            $response = $service->post(
                $this->platformPost->post->content,
                $this->platformPost->post->image_url
            );

            $this->platformPost->update([
                'status' => 'published',
                'external_id' => $response['id'],
                'posted_at' => now(),
                'metadata' => $response
            ]);

        } catch (\Exception $e) {
            $this->platformPost->update([
                'status' => 'failed',
                'metadata' => ['error' => $e->getMessage()]
            ]);
        }

        $this->updateParentPostStatus();
    }

    protected function updateParentPostStatus()
    {
        $this->platformPost->post->refresh()->updateStatus();
    }
}
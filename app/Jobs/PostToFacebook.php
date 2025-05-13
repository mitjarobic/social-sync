<?php

namespace App\Jobs;

use App\Support\DevHelper;
use App\Support\ImageStore;
use App\Models\PlatformPost;
use Illuminate\Bus\Queueable;
use App\Services\FacebookService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostToFacebook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PlatformPost $platformPost) {}

    public function handle(FacebookService $service)
    {
        try {
            $imageUrl = DevHelper::withNgrokUrl(ImageStore::url($this->platformPost->post->image_path));

            $result = $service->post(
                $this->platformPost->platform->external_id,
                $this->platformPost->platform->external_token,
                $this->platformPost->post->content,
                $imageUrl
            );

            $this->platformPost->update([
                'status' => \App\Enums\PlatformPostStatus::PUBLISHED,
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

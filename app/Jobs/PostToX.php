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

    public function __construct(public int $platformPostId) {}

    public function handle(XService $service)
    {
        try {
            $platformPost = PlatformPost::findOrFail($this->platformPostId);

            $imageUrl = $platformPost->post->image_path
                ? \App\Support\DevHelper::withNgrokUrl(\App\Support\ImageStore::url($platformPost->post->image_path))
                : null;

            $result = $service->post(
                $platformPost->platform->external_id,
                $platformPost->platform->external_token,
                $platformPost->post->content,
                $imageUrl
            );

            $platformPost->update([
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
            $platformPost->update([
                'status' => \App\Enums\PlatformPostStatus::FAILED,
                'metadata' => ['error' => $e->getMessage()]
            ]);
        }

        $this->updateParentPostStatus($platformPost);
    }

    protected function updateParentPostStatus(PlatformPost $platformPost)
    {
        $platformPost->post->refresh()->updateStatus();
    }
}
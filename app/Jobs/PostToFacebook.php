<?php

namespace App\Jobs;

use App\Support\DevHelper;
use App\Support\ImageStore;
use App\Models\PlatformPost;
use Illuminate\Bus\Queueable;
use App\Services\FacebookService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\SocialMediaImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostToFacebook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PlatformPost $platformPost) {}

    public function handle(FacebookService $service, SocialMediaImageGenerator $generator)
    {
        try {
            $jpegData = $generator->generate($this->platformPost->post->image_content, $this->platformPost->post->image_author);
            $filename = 'posts/' . now()->timestamp . '.jpg';

            $imageUrl = DevHelper::withNgrokUrl(ImageStore::save($filename, $jpegData));

            $result = $service->post(
                $this->platformPost->platform->external_id,
                $this->platformPost->platform->external_token,
                $this->platformPost->post->content,
                $imageUrl
            );

            $this->platformPost->update([
                // 'status' => PlatformPostStatus::PUBLISHED,
                'external_id' => $result['response']['id'],
                'external_url' => $result['url'],
                'posted_at' => now(),
                'metadata' => $result['response'],
            ]);

            if (ImageStore::exists($filename)) {
                ImageStore::delete($filename);
            }

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
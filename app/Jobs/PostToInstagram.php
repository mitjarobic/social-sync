<?php

namespace App\Jobs;

use App\Support\DevHelper;
use App\Support\ImageStore;
use App\Models\PlatformPost;
use App\Services\InstagramService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\SocialMediaImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostToInstagram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PlatformPost $platformPost) {}

    public function handle(InstagramService $service,  SocialMediaImageGenerator $generator)
    {
        try {
            dd(1);
            $jpegData = $generator->generate($this->platformPost->post->image_content, $this->platformPost->post->image_author);
            $filename = 'posts/test-' . now()->timestamp . '.jpg';

            $imageUrl = DevHelper::withNgrokUrl(ImageStore::save($filename, $jpegData));


            $response = $service->post(
                $this->platformPost->post->content,
                $imageUrl
            );

            $this->platformPost->update([
                'status' => \App\Enums\PlatformPostStatus::PUBLISHED,
                'external_id' => $response['id'],
                'posted_at' => now(),
                'metadata' => $response
            ]);
        } catch (\Exception $e) {
            $this->platformPost->update([
                'status' => \App\Enums\PlatformPostStatus::FAILED,
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

<?php

namespace App\Console\Commands;

use App\Services\XService;
use App\Support\DevHelper;
use App\Support\ImageStore;
use Illuminate\Console\Command;
use App\Services\FacebookService;
use App\Services\InstagramService;
use App\Services\SocialMediaImageGenerator;

class TestPlatformService extends Command
{
    // php artisan social:test facebook
    // php artisan social:test instagram
    // php artisan social:test x

    protected $signature = 'test:platform {platform}';
    protected $description = 'Send a direct test post to a platform via its service (no models, no queue)';

    public function handle(SocialMediaImageGenerator $generator)
    {
        $platform = $this->argument('platform');

        $caption = "⚡️ Test caption\nWith line breaks 🌿";

        $content = "A great quote on the image!";
        $author = "TCUA";

        // Options for image generation
        $options = [
            'contentFont' => 'sansSerif.ttf',
            'contentFontSize' => 112,
            'contentFontColor' => '#FFFFFF',
            'authorFont' => 'sansSerif.ttf',
            'authorFontSize' => 78,
            'authorFontColor' => '#FFFFFF',
            'bgColor' => '#000000',
            'extraOptions' => [
                'textAlignment' => 'center',
                'textPosition' => 'middle',
                'padding' => 20,
            ],
        ];

        // Generate and save image
        $jpegData = $generator->generate($content, $author, $options);
        $filename = 'posts/test-' . now()->timestamp . '.jpg';

        ImageStore::save($filename, $jpegData);
        $imageUrl = DevHelper::withNgrokUrl(ImageStore::url($filename));

        dump($imageUrl);

        try {
            // Mock page ID and token for testing
            $pageId = 'test_page_id';
            $pageToken = 'test_page_token';

            $response = match ($platform) {
                'facebook' => app(FacebookService::class)->post($pageId, $pageToken, $caption, $imageUrl),
                'instagram' => app(InstagramService::class)->post($pageId, $pageToken, $caption, $imageUrl),
                'x' => app(XService::class)->post($pageId, $pageToken, $caption, $imageUrl),
                default => throw new \Exception("Unknown platform: {$platform}"),
            };

            dump($response);
            $this->info("✅ Successfully posted to {$platform}.");
        } catch (\Throwable $e) {
            $this->error("❌ Failed to post to {$platform}: " . $e->getMessage());
        } finally {
            // Clean up the image file
            // if (ImageStore::exists($filename)) {
            //     ImageStore::delete($filename);
            // }
        }
    }
}

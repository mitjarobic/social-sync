<?php

namespace App\Console\Commands;

use App\Support\DevHelper;
use App\Support\ImageStore;
use App\Services\SocialMediaImageGenerator;
use Illuminate\Console\Command;

class TestImageAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:image';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test image creation and access';

    /**
     * Execute the console command.
     */
    public function handle(SocialMediaImageGenerator $generator)
    {

        // Test content with manual newlines
        $content = "A great quote\non the image!\n\nWith multiple lines\nand spacing";
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

        $this->line($imageUrl);
    }
}

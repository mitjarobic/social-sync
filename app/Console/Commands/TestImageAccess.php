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

        $content = "A great quote on the image!";
        $author = "TCUA";

        // Generate and save image
        $jpegData = $generator->generate($content, $author);
        $filename = 'posts/test-' . now()->timestamp . '.jpg';

        $imageUrl = DevHelper::withNgrokUrl(ImageStore::save($filename, $jpegData));

        $this->line($imageUrl);
    }
}

<?php

// app/Services/SocialMediaImageGenerator.php
namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class SocialMediaImageGenerator
{
    
    public function generate(string $content, ?string $author = null): string
    {

        
        $manager = new ImageManager(new Driver());
        
        $image = $manager->create(1080, 1080)->fill('#000000');

        // Main content (centered)
        $image->text($content, 540, 500, function($font) {
            $font->size(72);
            $font->filename(public_path('fonts/sansSerif.ttf'));

            $font->color('#ffffff');
            $font->align('center');
        });

        // Author signature if exists
        if ($author) {
            $image->text("— $author", 540, 600, function($font) {
                $font->filename(public_path('fonts/sansSerif.ttf'));
                $font->size(48);
                $font->color('#ffffff');
                $font->align('center');
            });
        }

        return $image->toJpeg()->toString();
    }
    
}

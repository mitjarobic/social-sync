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

        $contentFontSize = 112;
        $authorFontSize = 80;
        $lineHeight = 1.4;

        $yAxis = $author ? 480 : 540; // Adjust Y-axis based on author presence

        $maxWidth = 20; // Characters per line
    
        // Calculate text positioning
        $wrappedText = wordwrap($content, $maxWidth, "\n");
        $lines = explode("\n", $wrappedText);
        $totalHeight = count($lines) * $contentFontSize * $lineHeight;
        
        // Base Y position (centered vertically)
        $baseY = $author ? 590 : 640; // Adjust for author presence
        $startY = $baseY - ($totalHeight / 2);

        $yPos = 0;
        
        // Render each line with proper spacing
        foreach ($lines as $i => $line) {
            $yPos = $startY + ($i * $contentFontSize * $lineHeight);
            
            $image->text($line, 540, $yPos, function($font) use ($contentFontSize) {
                $font->filename(public_path('fonts/sansSerif.ttf'));
                $font->size($contentFontSize);
                $font->color('#FFFFFF');
                $font->align('center');
            });
        }

        // Add author if exists
        if ($author) {
            $image->text("— " . $author, 540, $yPos + 140, function ($font) use ($authorFontSize) {
                $font->filename(public_path('fonts/sansSerif.ttf'));
                $font->size($authorFontSize);
                $font->color('#FFFFFF');
                $font->align('center');
            });
        }

        return $image->toJpeg()->toString();
    }
}

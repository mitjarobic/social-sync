<?php

// app/Services/SocialMediaImageGenerator.php
namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class SocialMediaImageGenerator
{

    public static function generate(string $content, ?string $author = null, array $options = []): string
    {
        $manager = new ImageManager(new Driver());

        // Extract options with defaults
        $contentFontSize = $options['fontSize'] ?? 112;
        $authorFontSize = intval($contentFontSize * 0.7); // Author font is 70% of content font size
        $fontColor = $options['fontColor'] ?? '#FFFFFF';
        $bgColor = $options['bgColor'] ?? '#000000';
        $bgImagePath = $options['bgImagePath'] ?? null;
        $lineHeight = $options['extraOptions']['lineHeight'] ?? 1.4;
        $maxWidth = $options['extraOptions']['maxWidth'] ?? 20; // Characters per line

        // Create base image with background color
        $image = $manager->create(1080, 1080)->fill($bgColor);

        // Add background image if provided
        if (!empty($bgImagePath)) {
            try {
                $bgImage = $manager->read($bgImagePath);

                // Resize and crop background image to fit 1080x1080
                $bgImage->cover(1080, 1080);

                // Apply a slight darkening effect to ensure text is readable
                $bgImage->brightness(-10);

                // Overlay the background image
                $image->place($bgImage);
            } catch (\Exception $e) {
                error_log('Error processing background image: ' . $e->getMessage());
            }
        }

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

            $image->text($line, 540, $yPos, function ($font) use ($contentFontSize, $fontColor, $options) {
                $font->filename(public_path('fonts/' . ($options['font'] ?? 'sansSerif.ttf')));
                $font->size($contentFontSize);
                $font->color($fontColor);
                $font->align('center');
            });
        }

        // Add author if exists
        if ($author) {
            $image->text("— " . $author, 540, $yPos + 140, function ($font) use ($authorFontSize, $fontColor, $options) {
                $font->filename(public_path('fonts/' . ($options['font'] ?? 'sansSerif.ttf')));
                $font->size($authorFontSize);
                $font->color($fontColor);
                $font->align('center');
            });
        }

        return $image->toJpeg()->toString();
    }
}

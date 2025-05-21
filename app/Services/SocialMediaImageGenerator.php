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
        // Content font settings
        $contentFont = $options['contentFont'] ?? 'sansSerif.ttf';
        $contentFontSize = $options['contentFontSize'] ?? 112;
        $contentFontColor = $options['contentFontColor'] ?? '#FFFFFF';

        // Author font settings
        $authorFont = $options['authorFont'] ?? 'sansSerif.ttf';
        $authorFontSize = $options['authorFontSize'] ?? intval($contentFontSize * 0.7); // Default: 70% of content font size
        $authorFontColor = $options['authorFontColor'] ?? '#FFFFFF';

        // Background settings
        $bgColor = $options['bgColor'] ?? '#000000';
        $bgImagePath = $options['bgImagePath'] ?? null;

        // Layout settings
        $lineHeight = $options['extraOptions']['lineHeight'] ?? 1.4;
        $maxWidth = $options['extraOptions']['maxWidth'] ?? 20; // Characters per line
        $textAlignment = $options['extraOptions']['textAlignment'] ?? 'center';
        $textPosition = $options['extraOptions']['textPosition'] ?? 'middle';
        $padding = $options['extraOptions']['padding'] ?? 20;

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

        // Adjust base Y position based on text position setting
        $baseY = 540; // Default middle position
        if ($textPosition === 'top') {
            $baseY = $padding + ($totalHeight / 2) + 100;
        } else if ($textPosition === 'bottom') {
            $baseY = 1080 - $padding - ($totalHeight / 2) - ($author ? 140 : 0) - 100;
        } else {
            // Middle position (default)
            $baseY = $author ? 590 : 640; // Adjust for author presence
        }

        $startY = $baseY - ($totalHeight / 2);

        $yPos = 0;

        // Render each line with proper spacing
        foreach ($lines as $i => $line) {
            $yPos = $startY + ($i * $contentFontSize * $lineHeight);

            $image->text($line, 540, $yPos, function ($font) use ($contentFont, $contentFontSize, $contentFontColor, $textAlignment) {
                $font->filename(public_path('fonts/' . $contentFont));
                $font->size($contentFontSize);
                $font->color($contentFontColor);
                $font->align($textAlignment);
            });
        }

        // Add author if exists
        if ($author) {
            $image->text("— " . $author, 540, $yPos + 140, function ($font) use ($authorFont, $authorFontSize, $authorFontColor, $textAlignment) {
                $font->filename(public_path('fonts/' . $authorFont));
                $font->size($authorFontSize);
                $font->color($authorFontColor);
                $font->align($textAlignment);
            });
        }

        return $image->toJpeg()->toString();
    }
}

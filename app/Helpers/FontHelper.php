<?php

namespace App\Helpers;

class FontHelper
{
    public static function getAvailableFonts(): array
    {
        return  [
            'sansSerif.ttf' => 'Sans Serif',
            'serif.ttf' => 'Serif',
            'roboto.ttf' => 'Roboto',
            'opensans.ttf' => 'Open Sans',
            'lato.ttf' => 'Lato',
            'raleway.ttf' => 'Raleway',
            'oswald.ttf' => 'Oswald',
            'poppins.ttf' => 'Poppins',
            'playfair.ttf' => 'Playfair Display',
            'merriweather.ttf' => 'Merriweather',
            'pacifico.ttf' => 'Pacifico',
            'dancingscript.ttf' => 'Dancing Script',
        ];
    }

    public static function getStyledFontOptions($fonts = null): array
    {
        $fonts = $fonts ?? self::getAvailableFonts();
        
        $htmlFonts = [];
        foreach ($fonts as $key => $name) {
            // Extract font class name from the key (remove .ttf)
            $fontClass = str_replace('.ttf', '', $key);
            // $htmlFonts[$key] = '<span class="font-preview font-' . $fontClass . '">' . $name . '</span>';
            $htmlFonts[$key] = static::getRenderedFontName($key);
        }

        return $htmlFonts;
    }

    public static function getRenderedFontName(string $fontKey): string
    {
        $fontClass = str_replace('.ttf', '', $fontKey);
        return '<span class="font-preview font-' . $fontClass . '">' . static::getFontDisplayName($fontKey) . '</span>';
    }

    public static function getFontDisplayName(string $fontKey): string
    {
        $fonts = self::getAvailableFonts();
        return $fonts[$fontKey] ?? $fontKey;
    }
}

<?php

namespace App\Helpers;

class FontHelper
{
    /**
     * Get the list of available fonts with HTML preview spans
     * 
     * @param bool $withHtml Whether to include HTML preview spans
     * @return array
     */
    public static function getFontOptions(bool $withHtml = true): array
    {
        $fonts = [
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

        if (!$withHtml) {
            return $fonts;
        }

        $htmlFonts = [];
        foreach ($fonts as $key => $name) {
            // Extract font class name from the key (remove .ttf)
            $fontClass = str_replace('.ttf', '', $key);
            $htmlFonts[$key] = '<span class="font-preview font-' . $fontClass . '">' . $name . '</span>';
        }

        return $htmlFonts;
    }

    /**
     * Get the display name for a font
     * 
     * @param string $fontKey The font key (e.g., 'sansSerif.ttf')
     * @return string The display name
     */
    public static function getFontDisplayName(string $fontKey): string
    {
        $fonts = self::getFontOptions(false);
        return $fonts[$fontKey] ?? $fontKey;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class DevHelper
{
    public static function withNgrokUrl(string $url): string
    {
        if (app()->environment('local')) {
            $url = str_replace(
                config('app.url'),
                'https://superb-liked-newt.ngrok-free.app',
                $url
            );
        }

        return $url;
    }

    public static function isImageAccessible(string $url): bool
    {
        try {
            $response = Http::timeout(5)->head($url);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }


}
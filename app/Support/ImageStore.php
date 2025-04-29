<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;


class ImageStore
{
    public static function save(string $path, $imageData): void
    {
        Storage::disk('public')->put($path, $imageData);
    }

    public static function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    public static function exists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }

    public static function url(string $path): string
    {
        return asset("storage/{$path}");
    }  
}

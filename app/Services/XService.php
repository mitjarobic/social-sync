<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class XService
{
    public function post(string $content, ?string $imageUrl = null): array
    {
        $payload = [
            'message' => $content,
            'access_token' => config('services.facebook.token')
        ];

        if ($imageUrl) {
            $payload['url'] = $imageUrl;
            $endpoint = 'https://graph.facebook.com/v18.0/me/photos';
        } else {
            $endpoint = 'https://graph.facebook.com/v18.0/me/feed';
        }

        $response = Http::post($endpoint, $payload);

        if ($response->failed()) {
            throw new \Exception($response->json('error.message'));
        }

        return $response->json();
    }
}
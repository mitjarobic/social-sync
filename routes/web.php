<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Services\SocialMediaImageGenerator;
use App\Http\Controllers\FacebookController;
use Laravel\Horizon\Horizon;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-image', function (Request $request) {
    try {
        // Build options array from request parameters
        $options = [
            'contentFont' => $request->input('contentFont', 'sansSerif.ttf'),
            'contentFontSize' => (int) $request->input('contentFontSize', 112),
            'contentFontColor' => $request->input('contentFontColor', '#FFFFFF'),
            'authorFont' => $request->input('authorFont', 'sansSerif.ttf'),
            'authorFontSize' => (int) $request->input('authorFontSize', 78),
            'authorFontColor' => $request->input('authorFontColor', '#FFFFFF'),
            'bgColor' => $request->input('bgColor', '#000000'),
            'bgImagePath' => $request->input('bgImagePath'),
            'extraOptions' => [
                'lineHeight' => (float) $request->input('lineHeight', 1.4),
                'maxWidth' => (int) $request->input('maxWidth', 20),
                'textAlignment' => $request->input('textAlignment', 'center'),
                'textPosition' => $request->input('textPosition', 'middle'),
                'padding' => (int) $request->input('padding', 20),
            ],
        ];

        return response(
            app(SocialMediaImageGenerator::class)->generate(
                $request->input('content', 'Default Content'),
                $request->input('author', 'Author'),
                $options
            )
        )->header('Content-Type', 'image/jpeg');

    } catch (\Exception $e) {
        return response("Failed to generate image: " . $e->getMessage(), 500);
    }
});

Route::get('/privacy', function () {
   return "Privacy Policy";
});

Route::get('/facebook/redirect', [FacebookController::class, 'redirect'])->name('facebook.redirect');
Route::get('/facebook/callback', [FacebookController::class, 'callback'])->name('facebook.callback');

//test route for sync platforms
Route::get('/sync-platforms', function () {
    if (Auth::check()) {
        (new \App\Services\PlatformSyncService(Auth::user()))->syncPlatforms();
        return "Platforms synced successfully";
    }
    return "User not authenticated";
})->name('sync-platforms');

// Note: Facebook & Instagram Webhook Routes are in api.php
// OAuth routes are here in web.php because they're user-facing

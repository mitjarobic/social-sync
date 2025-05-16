<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\SocialMediaImageGenerator;
use App\Http\Controllers\FacebookController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-image', function (Request $request) {
    try {
        // Build options array from request parameters
        $options = [
            'font' => $request->input('font', 'sansSerif.ttf'),
            'fontSize' => (int) $request->input('fontSize', 112),
            'fontColor' => $request->input('fontColor', '#FFFFFF'),
            'bgColor' => $request->input('bgColor', '#000000'),
            'bgImagePath' => $request->input('bgImagePath'),
            'extraOptions' => [
                'lineHeight' => (float) $request->input('lineHeight', 1.4),
                'maxWidth' => (int) $request->input('maxWidth', 20),
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

//test rout for sync platforms
Route::get('/sync-platforms', function () {
    (new \App\Services\PlatformSyncService(auth()->user()))->syncPlatforms();
})->name('sync-platforms');




<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\SocialMediaImageGenerator;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-image', function (Request $request) {
    try {
        return response(
            app(SocialMediaImageGenerator::class)->generate(
                $request->input('content', 'Default Content'),
                $request->input('author', 'Author')
            )
        )->header('Content-Type', 'image/jpeg');
        
    } catch (\Exception $e) {
        return response("Failed to generate image: " . $e->getMessage(), 500);
    }
});

Route::get('/debug-preview', function () {
    return view('filament.custom.social-preview', [
        'content' => 'Test Content',
        'author' => 'Test Author',
        'version' => 1
    ]);
});

Route::get('/privacy', function () {
   return "Privacy Policy";
});




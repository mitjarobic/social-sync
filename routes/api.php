<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacebookWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Facebook & Instagram Webhook Routes
// These are in api.php because they're system-to-system endpoints
Route::prefix('webhooks')->middleware('api')->group(function () {
    // Facebook webhook verification (GET request)
    Route::get('/facebook', [FacebookWebhookController::class, 'verify']);

    // Facebook webhook notifications (POST request)
    Route::post('/facebook', [FacebookWebhookController::class, 'handle']);
});

<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Route::get('/webhook', [WhatsAppController::class, 'verify']);
// Route::post('/webhook', [WhatsAppController::class, 'webhook']);

//Route::post('/telegram/webhook', [ChatController::class, 'handle']);

//Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
Route::post('/telegram/webhook', [ChatController::class, 'handle'])
    ->withoutMiddleware(['throttle', 'auth:sanctum']);

//Route::post('/telegram/webhook', [TelegramController::class, 'handle']);
Route::get('/test', function () {
    return 'API OK';
});
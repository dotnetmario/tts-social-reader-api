<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TtsAudioController;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);



    Route::middleware([
        App\Http\Middleware\CustomSanctumAuth::class
    ])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);


        // // user chats
        // Route::prefix('chats')->group(function () {
        //     Route::get('/', [ChatController::class, 'chats']);
        //     Route::get('/{chat_id}', [ChatController::class, 'chat']);

        //     Route::post('/{chat_id}', [ChatController::class, 'sendChat']);
        // });
        Route::post('/generate-audio', [TtsAudioController::class, 'generateAudio']);
    });

    // Route::middleware([
    //     App\Http\Middleware\CustomSanctumAuth::class
    // ])->prefix('tts-reader')->group(function () {

    // });
});








// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

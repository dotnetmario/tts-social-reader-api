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


        Route::post('/generate-audio', [TtsAudioController::class, 'generateAudio']);
    });
});

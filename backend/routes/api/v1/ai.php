<?php

use App\Http\Controllers\Api\V1\Ai\ChatController;
use Illuminate\Support\Facades\Route;

Route::post('/ai/chat', [ChatController::class, 'chat'])
    ->middleware('auth:sanctum')
    ->name('ai.chat');

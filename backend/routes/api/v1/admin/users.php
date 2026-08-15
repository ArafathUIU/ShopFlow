<?php

use App\Http\Controllers\Api\V1\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}', [UserController::class, 'show']);
Route::patch('/users/{user}', [UserController::class, 'update']);
Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate']);
Route::post('/users/{user}/activate', [UserController::class, 'activate']);

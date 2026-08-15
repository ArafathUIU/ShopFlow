<?php

use App\Http\Controllers\Api\V1\Admin\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:admin,manager'])->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('/categories/{category}/activate', [CategoryController::class, 'activate']);
    Route::post('/categories/{category}/deactivate', [CategoryController::class, 'deactivate']);
});
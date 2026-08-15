<?php

use App\Http\Controllers\Api\V1\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::patch('/products/{product}', [ProductController::class, 'update']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);
Route::post('/products/{product}/restore', [ProductController::class, 'restore']);
Route::post('/products/{product}/archive', [ProductController::class, 'archive']);
Route::post('/products/{product}/unarchive', [ProductController::class, 'unarchive']);
Route::post('/products/{product}/images', [ProductController::class, 'attachImage']);
Route::delete('/products/{product}/images/{image}', [ProductController::class, 'detachImage']);

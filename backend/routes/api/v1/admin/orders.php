<?php

use App\Http\Controllers\Api\V1\Admin\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{order}', [OrderController::class, 'show']);
Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

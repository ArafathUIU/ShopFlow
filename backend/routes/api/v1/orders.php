<?php

use App\Http\Controllers\Api\V1\Orders\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')
    ->middleware('auth:sanctum')
    ->name('orders.')
    ->group(function (): void {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('{order}', [OrderController::class, 'show'])->name('show');
        Route::post('{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    });

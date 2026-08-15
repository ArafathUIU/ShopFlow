<?php

use App\Http\Controllers\Api\V1\Cart\CartController;
use Illuminate\Support\Facades\Route;

Route::prefix('cart')
    ->middleware('auth:sanctum')
    ->name('cart.')
    ->group(function (): void {
        Route::get('/', [CartController::class, 'show'])->name('show');
        Route::post('/items', [CartController::class, 'addItem'])->name('items.add');
        Route::patch('/items/{item}', [CartController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}', [CartController::class, 'removeItem'])->name('items.remove');
        Route::delete('/', [CartController::class, 'clear'])->name('clear');
        Route::post('/coupon', [CartController::class, 'applyCoupon'])->name('coupon.apply');
        Route::delete('/coupon', [CartController::class, 'removeCoupon'])->name('coupon.remove');
    });

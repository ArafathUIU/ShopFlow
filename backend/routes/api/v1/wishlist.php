<?php

use App\Http\Controllers\Api\V1\Wishlist\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('wishlist')
    ->middleware('auth:sanctum')
    ->name('wishlist.')
    ->group(function (): void {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/', [WishlistController::class, 'store'])->name('store');
        Route::delete('{product}', [WishlistController::class, 'destroy'])->name('destroy');
    });

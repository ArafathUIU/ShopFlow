<?php

use App\Http\Controllers\Api\V1\Catalog\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->name('products.')->group(function (): void {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('{product:slug}', [ProductController::class, 'show'])->name('show');
});

<?php

use App\Http\Controllers\Api\V1\Catalog\CategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->name('categories.')->group(function (): void {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('{category:slug}', [CategoryController::class, 'show'])->name('show');
});

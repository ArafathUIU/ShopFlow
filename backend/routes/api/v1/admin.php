<?php

use Illuminate\Support\Facades\Route;

// Administrative API — requires an authenticated admin (or manager) user.
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin,manager'])
    ->group(function (): void {
        require __DIR__.'/admin/products.php';
        require __DIR__.'/admin/inventory.php';
    });

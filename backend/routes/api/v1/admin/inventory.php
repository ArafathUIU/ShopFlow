<?php

use App\Http\Controllers\Api\V1\Admin\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:admin,manager'])->group(function (): void {
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory/{productId}/adjust', [InventoryController::class, 'adjust']);
});

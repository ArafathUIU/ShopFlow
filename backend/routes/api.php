<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Versioned under /api/v1. Group structure per domain:
|
|   /api/v1/auth        - authentication
|   /api/v1/products    - catalog
|   /api/v1/categories  - catalog
|   /api/v1/cart        - shopping cart
|   /api/v1/wishlist    - wishlist
|   /api/v1/orders      - orders
|   /api/v1/payments    - payments / checkout
|   /api/v1/coupons     - discounts
|   /api/v1/inventory   - stock management (admin)
|   /api/v1/users       - user management (admin)
|   /api/v1/analytics   - business analytics (admin)
|   /api/v1/admin       - administrative umbrella
|
*/

Route::prefix('v1')->group(function (): void {

    // Health check — lightweight, unauthenticated.
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'service' => 'shopflow-api',
        'version' => 'v1',
        'timestamp' => now()->toIso8601String(),
    ]));

    // Domain routes are registered in dedicated files as they are built:
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/products.php';
    require __DIR__.'/api/v1/categories.php';
    require __DIR__.'/api/v1/cart.php';
    require __DIR__.'/api/v1/wishlist.php';
    require __DIR__.'/api/v1/orders.php';
    require __DIR__.'/api/v1/payments.php';
    require __DIR__.'/api/v1/coupons.php';
    // require __DIR__.'/api/v1/inventory.php';
    // require __DIR__.'/api/v1/users.php';
    // require __DIR__.'/api/v1/analytics.php';
    require __DIR__.'/api/v1/admin.php';
});

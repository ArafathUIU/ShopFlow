<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('the full schema migrates and exposes every domain table', function () {
    $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table'"))
        ->pluck('name')
        ->all();

    expect($tables)->toContain(
        'users',
        'addresses',
        'categories',
        'products',
        'product_images',
        'inventories',
        'inventory_transactions',
        'carts',
        'cart_items',
        'wishlist_items',
        'coupons',
        'coupon_usages',
        'orders',
        'order_items',
        'order_status_history',
        'payments',
        'webhook_events',
    );
});

test('orders require a non-null user', function () {
    $this->expectException(QueryException::class);

    DB::table('orders')->insert([
        'order_number' => 'SF-TEST',
        'user_id' => null,
    ]);
});

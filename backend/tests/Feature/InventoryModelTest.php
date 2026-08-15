<?php

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('available quantity excludes reserved stock', function () {
    $inventory = Inventory::factory()->create(['quantity' => 10, 'reserved_quantity' => 3]);

    expect($inventory->availableQuantity())->toBe(7)
        ->and($inventory->hasSufficient(7))->toBeTrue()
        ->and($inventory->hasSufficient(8))->toBeFalse();
});

test('inventory stock states', function () {
    $wellStocked = Inventory::factory()->inStock(100)->create();
    $low = Inventory::factory()->lowStock(2)->create();
    $none = Inventory::factory()->outOfStock()->create();

    expect($wellStocked->isOutOfStock())->toBeFalse()
        ->and($wellStocked->isLowStock())->toBeFalse()
        ->and($low->isLowStock())->toBeTrue()
        ->and($none->isOutOfStock())->toBeTrue();
});

test('low stock and out of stock scopes', function () {
    Inventory::factory()->inStock(100)->create();
    Inventory::factory()->lowStock(2)->create();
    Inventory::factory()->outOfStock()->create();

    expect(Inventory::query()->lowStock()->count())->toBe(2)
        ->and(Inventory::query()->outOfStock()->count())->toBe(1);
});

test('inventory belongs to a product', function () {
    $product = Product::factory()->create();
    $inventory = Inventory::factory()->create(['product_id' => $product->id]);

    expect($inventory->product->is($product))->toBeTrue()
        ->and($product->inventory->is($inventory))->toBeTrue();
});

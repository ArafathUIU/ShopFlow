<?php

use App\Enums\ProductStatus;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product money fields cast to Money value objects', function () {
    $product = Product::factory()->create(['price' => 2499]);

    expect($product->price->cents())->toBe(2499)
        ->and((string) $product->price)->toBe('2499');
});

test('active scope only returns storefront-visible products', function () {
    Product::factory()->create();
    Product::factory()->draft()->create();
    Product::factory()->archived()->create();

    $active = Product::query()->active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->status)->toBe(ProductStatus::Active);
});

test('featured scope returns only featured products', function () {
    Product::factory()->featured()->create();
    Product::factory()->create();

    expect(Product::query()->featured()->count())->toBe(1);
});

test('search scope matches name, description and sku', function () {
    $product = Product::factory()->create(['name' => 'Wireless Headphones', 'sku' => 'AUDIO-99']);

    expect(Product::query()->search('wireless')->pluck('id'))->toContain($product->id)
        ->and(Product::query()->search('audio-99')->pluck('id'))->toContain($product->id);
});

test('price scope filters by range', function () {
    Product::factory()->create(['price' => 1000]);
    Product::factory()->create(['price' => 5000]);
    Product::factory()->create(['price' => 9000]);

    $within = Product::query()->priceBetween(2000, 6000)->get();

    expect($within)->toHaveCount(1)
        ->and($within->first()->price->cents())->toBe(5000);
});

test('product can detect sale pricing', function () {
    $regular = Product::factory()->create(['price' => 1000, 'compare_at_price' => null]);
    $onSale = Product::factory()->create(['price' => 1000, 'compare_at_price' => 1500]);
    $noSale = Product::factory()->create(['price' => 1000, 'compare_at_price' => 800]);

    expect($regular->isOnSale())->toBeFalse()
        ->and($onSale->isOnSale())->toBeTrue()
        ->and($onSale->saleAmount()->cents())->toBe(500)
        ->and($noSale->isOnSale())->toBeFalse();
});

test('withStock scope only returns products with available inventory', function () {
    $inStock = Product::factory()->create();
    Inventory::factory()->inStock(10)->create(['product_id' => $inStock->id]);

    $outOfStock = Product::factory()->create();
    Inventory::factory()->outOfStock()->create(['product_id' => $outOfStock->id]);

    $reserved = Product::factory()->create();
    Inventory::factory()->create(['product_id' => $reserved->id, 'quantity' => 5, 'reserved_quantity' => 5]);

    $result = Product::query()->withStock()->pluck('id');

    expect($result)->toContain($inStock->id)
        ->not->toContain($outOfStock->id)
        ->not->toContain($reserved->id);
});

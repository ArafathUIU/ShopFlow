<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function productWithStock(array $attributes = [], int $quantity = 10): Product
{
    $product = Product::factory()->create($attributes);

    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => $quantity,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 5,
    ]);

    return $product;
}

it('lists active products with stock and metadata', function (): void {
    $featured = productWithStock([
        'price' => 2499,
        'is_featured' => true,
    ]);
    productWithStock();
    productWithStock(['status' => ProductStatus::Draft]);
    productWithStock(['status' => ProductStatus::Archived, 'archived_at' => now()]);

    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.pagination.total', 2)
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'slug' => $featured->slug,
            'in_stock' => true,
            'available_quantity' => 10,
            'price' => ['cents' => 2499, 'formatted' => '24.99'],
        ]);
});

it('respects the per_page parameter', function (): void {
    productWithStock();
    productWithStock();

    $this->getJson('/api/v1/products?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.pagination.per_page', 1)
        ->assertJsonPath('meta.pagination.last_page', 2);
});

it('filters products by category', function (): void {
    $electronics = Category::factory()->create(['name' => 'Electronics']);
    $books = Category::factory()->create(['name' => 'Books']);

    productWithStock(['category_id' => $electronics->id]);
    productWithStock(['category_id' => $books->id]);

    $this->getJson("/api/v1/products?category={$electronics->id}")
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 1)
        ->assertJsonPath('data.0.category.id', $electronics->id);
});

it('searches products by name', function (): void {
    $wireless = productWithStock(['name' => 'Wireless Headphones']);
    productWithStock(['name' => 'Corded Mouse']);

    $this->getJson('/api/v1/products?search=wireless')
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 1)
        ->assertJsonPath('data.0.id', $wireless->id);
});

it('filters products by price range', function (): void {
    productWithStock(['price' => 1000]);
    productWithStock(['price' => 5000]);
    productWithStock(['price' => 9000]);

    $this->getJson('/api/v1/products?min_price=20&max_price=60')
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 1)
        ->assertJsonPath('data.0.price.cents', 5000);
});

it('sorts products by price', function (): void {
    productWithStock(['price' => 9000, 'name' => 'A']);
    productWithStock(['price' => 1000, 'name' => 'B']);

    $this->getJson('/api/v1/products?sort=price_asc')
        ->assertOk()
        ->assertJsonPath('data.0.price.cents', 1000);

    $this->getJson('/api/v1/products?sort=price_desc')
        ->assertOk()
        ->assertJsonPath('data.0.price.cents', 9000);
});

it('sorts products by popularity', function (): void {
    $cheap = productWithStock(['price' => 1000]);
    $popular = productWithStock(['price' => 2000]);
    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $popular->id,
        'unit_price' => 2000,
        'quantity' => 5,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $cheap->id,
        'unit_price' => 1000,
        'quantity' => 2,
    ]);

    $this->getJson('/api/v1/products?sort=popular')
        ->assertOk()
        ->assertJsonPath('data.0.id', $popular->id);
});

it('rejects an invalid sort value', function (): void {
    $this->getJson('/api/v1/products?sort=bogus')
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['sort']]);
});

it('shows a product by slug', function (): void {
    $product = productWithStock(['name' => 'Premium Speaker']);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $product->slug)
        ->assertJsonPath('data.name', 'Premium Speaker')
        ->assertJsonStructure([
            'data' => [
                'price' => ['cents', 'formatted'],
                'in_stock',
                'available_quantity',
            ],
        ]);
});

it('returns 404 for an unknown product slug', function (): void {
    $this->getJson('/api/v1/products/does-not-exist')
        ->assertStatus(404)
        ->assertJsonPath('success', false);
});

it('returns 404 for a non-active product', function (): void {
    $product = productWithStock(['status' => ProductStatus::Draft]);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertStatus(404);
});

it('lists the category tree with product counts', function (): void {
    $electronics = Category::factory()->create(['name' => 'Electronics', 'sort_order' => 1]);
    $laptops = Category::factory()->create(['name' => 'Laptops', 'parent_id' => $electronics->id]);
    productWithStock(['category_id' => $laptops->id]);

    Category::factory()->inactive()->create(['name' => 'Hidden']);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Electronics')
        ->assertJsonPath('data.0.product_count', 0)
        ->assertJsonPath('data.0.children.0.name', 'Laptops')
        ->assertJsonPath('data.0.children.0.product_count', 1);
});

it('shows a category with its products', function (): void {
    $electronics = Category::factory()->create(['name' => 'Electronics']);
    $phone = productWithStock(['category_id' => $electronics->id, 'name' => 'Phone']);
    productWithStock(['category_id' => Category::factory()]);

    $this->getJson("/api/v1/categories/{$electronics->slug}")
        ->assertOk()
        ->assertJsonPath('data.category.name', 'Electronics')
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.products.0.id', $phone->id)
        ->assertJsonPath('meta.pagination.total', 1);
});

it('returns 404 for an inactive category', function (): void {
    $category = Category::factory()->inactive()->create();

    $this->getJson("/api/v1/categories/{$category->slug}")
        ->assertStatus(404);
});

<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function wishlistProduct(): Product
{
    $product = Product::factory()->create();

    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 3,
    ]);

    return $product;
}

it('requires authentication', function (): void {
    $this->getJson('/api/v1/wishlist')->assertStatus(401);
});

it('returns an empty wishlist', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/wishlist')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.product_ids', []);
});

it('adds a product to the wishlist', function (): void {
    $user = User::factory()->create();
    $product = wishlistProduct();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/wishlist', ['product_id' => $product->id])
        ->assertCreated()
        ->assertJsonPath('message', 'Added to wishlist.')
        ->assertJsonPath('data.product.id', $product->id);

    $this->assertDatabaseHas('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);
});

it('is idempotent when adding an existing product', function (): void {
    $user = User::factory()->create();
    $product = wishlistProduct();
    $user->wishlistItems()->create(['product_id' => $product->id]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/wishlist', ['product_id' => $product->id])
        ->assertOk()
        ->assertJsonPath('message', 'Already in wishlist.');

    $this->assertDatabaseCount('wishlist_items', 1);
});

it('lists wishlist items with product details', function (): void {
    $user = User::factory()->create();
    $product = wishlistProduct();
    $user->wishlistItems()->create(['product_id' => $product->id]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/wishlist')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.product_ids', [$product->id])
        ->assertJsonPath('data.0.product.id', $product->id);
});

it('removes a product from the wishlist', function (): void {
    $user = User::factory()->create();
    $product = wishlistProduct();
    $user->wishlistItems()->create(['product_id' => $product->id]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/wishlist/{$product->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Removed from wishlist.');

    $this->assertDatabaseMissing('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);
});

it('ignores removing a product that is not wishlisted', function (): void {
    $user = User::factory()->create();
    $product = wishlistProduct();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/wishlist/{$product->id}")
        ->assertOk();
});

it('does not expose another users wishlist', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $product = wishlistProduct();
    $alice->wishlistItems()->create(['product_id' => $product->id]);

    $this->actingAs($bob, 'sanctum')
        ->getJson('/api/v1/wishlist')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rejects adding a non-existent product', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/wishlist', ['product_id' => 999999])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['product_id']]);
});

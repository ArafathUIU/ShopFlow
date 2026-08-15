<?php

use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cartProduct(int $qty = 10): Product
{
    $product = Product::factory()->create();

    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => $qty,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 3,
    ]);

    return $product;
}

it('requires authentication', function (): void {
    $this->getJson('/api/v1/cart')->assertStatus(401);
});

it('returns an empty cart for a new user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.item_count', 0)
        ->assertJsonPath('data.subtotal.cents', 0)
        ->assertJsonCount(0, 'data.items');

    $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
});

it('adds a product to the cart', function (): void {
    $user = User::factory()->create();
    $product = cartProduct();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.product.id', $product->id)
        ->assertJsonPath('data.quantity', 2)
        ->assertJsonPath('data.unit_price.cents', $product->price->cents());

    $this->assertDatabaseHas('cart_items', [
        'cart_id' => Cart::query()->where('user_id', $user->id)->first()->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

it('increments quantity when the same product is added again', function (): void {
    $user = User::factory()->create();
    $product = cartProduct();

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 3])
        ->assertCreated()
        ->assertJsonPath('data.quantity', 4);
});

it('rejects adding more than available stock', function (): void {
    $user = User::factory()->create();
    $product = cartProduct(5);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 6])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Only 5 units of "'.$product->name.'" are available.');
});

it('rejects adding an inactive product', function (): void {
    $user = User::factory()->create();
    $product = cartProduct();
    $product->update(['status' => ProductStatus::Draft]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This product is no longer available.');
});

it('updates the quantity of a cart item', function (): void {
    $user = User::factory()->create();
    $product = cartProduct();
    $cart = Cart::factory()->create(['user_id' => $user->id]);
    $item = $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => $product->price->cents(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/v1/cart/items/{$item->id}", ['quantity' => 4])
        ->assertOk()
        ->assertJsonPath('data.quantity', 4);
});

it('rejects updating beyond available stock', function (): void {
    $user = User::factory()->create();
    $product = cartProduct(2);
    $cart = Cart::factory()->create(['user_id' => $user->id]);
    $item = $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => $product->price->cents(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/v1/cart/items/{$item->id}", ['quantity' => 9])
        ->assertStatus(422);
});

it('does not let a user touch another users cart items', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $product = cartProduct();
    $cart = Cart::factory()->create(['user_id' => $owner->id]);
    $item = $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => $product->price->cents(),
    ]);

    $this->actingAs($intruder, 'sanctum')
        ->patchJson("/api/v1/cart/items/{$item->id}", ['quantity' => 2])
        ->assertStatus(404);
});

it('removes an item from the cart', function (): void {
    $user = User::factory()->create();
    $product = cartProduct();
    $cart = Cart::factory()->create(['user_id' => $user->id]);
    $item = $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => $product->price->cents(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/cart/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Item removed from cart.');

    $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
});

it('clears the whole cart', function (): void {
    $user = User::factory()->create();
    $product = cartProduct();
    $cart = Cart::factory()->create(['user_id' => $user->id]);
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => $product->price->cents(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/cart')
        ->assertOk();

    $this->assertDatabaseCount('cart_items', 0);
    $this->assertNull($cart->fresh()->coupon_id);
});

it('applies a valid coupon to the cart', function (): void {
    $user = User::factory()->create();
    $product = cartProduct();
    $coupon = Coupon::factory()->create(['code' => 'SAVE10', 'value' => 10]);
    $cart = Cart::factory()->create(['user_id' => $user->id]);
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => $product->price->cents(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/cart/coupon', ['code' => 'SAVE10'])
        ->assertOk()
        ->assertJsonPath('data.coupon.code', 'SAVE10');

    $this->assertEquals($coupon->id, $cart->fresh()->coupon_id);
});

it('rejects an invalid coupon code', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/cart/coupon', ['code' => 'NOPE'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Coupon code not found.');
});

it('rejects a coupon below the minimum order amount', function (): void {
    $user = User::factory()->create();
    $product = cartProduct();
    $coupon = Coupon::factory()->create([
        'code' => 'BIGSAVE',
        'value' => 2000,
        'min_order_amount' => 5000,
    ]);
    $cart = Cart::factory()->create(['user_id' => $user->id]);
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 1000,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/cart/coupon', ['code' => 'BIGSAVE'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This coupon requires a minimum order of $50.00.');
});

it('removes the coupon from the cart', function (): void {
    $user = User::factory()->create();
    $product = cartProduct();
    $coupon = Coupon::factory()->create(['code' => 'SAVE10', 'value' => 10]);
    $cart = Cart::factory()->create(['user_id' => $user->id, 'coupon_id' => $coupon->id]);
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => $product->price->cents(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/cart/coupon')
        ->assertOk()
        ->assertJsonPath('data.coupon', null);

    $this->assertNull($cart->fresh()->coupon_id);
});

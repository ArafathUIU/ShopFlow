<?php

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function checkoutAddress(): array
{
    return [
        'line1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
    ];
}

function checkoutUser(array $overrides = []): User
{
    return User::factory()->create($overrides);
}

function seededCart(User $user, array $pairs, ?Coupon $coupon = null): Cart
{
    $cart = Cart::factory()->create(['user_id' => $user->id, 'coupon_id' => $coupon?->id]);

    foreach ($pairs as [$product, $quantity]) {
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $product->price->cents(),
        ]);
    }

    return $cart;
}

function stockedProduct(int $price = 5000, int $qty = 10): Product
{
    $product = Product::factory()->create(['price' => $price]);

    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => $qty,
        'reserved_quantity' => 0,
        'low_stock_threshold' => 3,
    ]);

    return $product;
}

it('requires authentication', function (): void {
    $this->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])->assertStatus(401);
});

it('places an order from the cart and reserves stock', function (): void {
    $user = checkoutUser();
    $product = stockedProduct(5000, 10);
    seededCart($user, [[$product, 2]]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.subtotal.cents', 10000)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonStructure(['data' => ['order_number']]);

    $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'status' => 'pending']);
    $this->assertDatabaseCount('order_items', 1);
    $this->assertDatabaseCount('cart_items', 0);

    $this->assertEquals(2, $product->fresh()->inventory->reserved_quantity);

    $this->assertDatabaseHas('inventory_transactions', [
        'type' => InventoryTransactionType::Reservation->value,
        'quantity_change' => -2,
    ]);
});

it('applies a coupon discount to the order total', function (): void {
    $user = checkoutUser();
    $product = stockedProduct(10000, 5);
    $coupon = Coupon::factory()->percent(10)->create(['code' => 'SAVE10']);
    seededCart($user, [[$product, 1]], $coupon);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])
        ->assertCreated();

    $order = Order::query()->first();

    expect($order->subtotal->cents())->toBe(10000)
        ->and($order->discount->cents())->toBe(1000)
        ->and($order->tax->cents())->toBe(720)
        ->and($order->shipping_fee->cents())->toBe(0)
        ->and($order->total->cents())->toBe(9720);

    $this->assertDatabaseHas('coupon_usages', ['coupon_id' => $coupon->id, 'order_id' => $order->id]);
    expect($coupon->fresh()->times_used)->toBe(1);
});

it('charges flat shipping below the free threshold', function (): void {
    $user = checkoutUser();
    $product = stockedProduct(2000, 5);
    seededCart($user, [[$product, 1]]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])
        ->assertCreated();

    $order = Order::query()->first();
    expect($order->shipping_fee->cents())->toBe(500);
});

it('rejects checkout with an empty cart', function (): void {
    $user = checkoutUser();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Your cart is empty.');
});

it('rejects checkout when stock is insufficient', function (): void {
    $user = checkoutUser();
    $product = stockedProduct(5000, 2);
    seededCart($user, [[$product, 5]]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])
        ->assertStatus(422);

    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('inventory_transactions', 0);
});

it('reserves stock without overselling across concurrent orders', function (): void {
    $user = checkoutUser();
    $product = stockedProduct(1000, 3);

    $cart = seededCart($user, [[$product, 2]]);
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])->assertCreated();

    $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => $product->price->cents(),
    ]);
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])
        ->assertStatus(422);

    expect($product->fresh()->inventory->reserved_quantity)->toBe(2)
        ->and($product->fresh()->inventory->availableQuantity())->toBe(1);

    $this->assertDatabaseCount('orders', 1);
});

it('rejects adding an inactive product to checkout via cart', function (): void {
    $user = checkoutUser();
    $product = stockedProduct();
    $product->update(['status' => ProductStatus::Draft]);
    seededCart($user, [[$product, 1]]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])
        ->assertStatus(422);
});

it('lists only the authenticated users orders', function (): void {
    $alice = checkoutUser();
    $bob = checkoutUser();

    $aliceOrder = Order::factory()->create(['user_id' => $alice->id]);
    Order::factory()->create(['user_id' => $bob->id]);

    $this->actingAs($alice, 'sanctum')
        ->getJson('/api/v1/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $aliceOrder->id);
});

it('shows an order owned by the user', function (): void {
    $user = checkoutUser();
    $product = stockedProduct();
    $order = Order::factory()->create(['user_id' => $user->id]);
    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'sku' => $product->sku,
        'unit_price' => $product->price->cents(),
        'quantity' => 1,
        'total' => $product->price->cents(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('data.order_number', $order->order_number)
        ->assertJsonCount(1, 'data.items');
});

it('hides another users order', function (): void {
    $user = checkoutUser();
    $other = Order::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/orders/{$other->id}")
        ->assertStatus(404);
});

it('cancels a pending order and releases reserved stock', function (): void {
    $user = checkoutUser();
    $product = stockedProduct(5000, 10);
    seededCart($user, [[$product, 2]]);
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/orders', ['shipping_address' => checkoutAddress()])->assertCreated();

    $order = Order::query()->first();
    expect($product->fresh()->inventory->reserved_quantity)->toBe(2);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($product->fresh()->inventory->reserved_quantity)->toBe(0);
    $this->assertDatabaseHas('order_status_history', [
        'order_id' => $order->id,
        'to_status' => OrderStatus::Cancelled->value,
    ]);
    $this->assertDatabaseHas('inventory_transactions', [
        'type' => InventoryTransactionType::Release->value,
    ]);
});

it('cannot cancel a shipped order', function (): void {
    $user = checkoutUser();
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Shipped]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/orders/{$order->id}/cancel")
        ->assertStatus(422)
        ->assertJsonPath('message', 'This order cannot be cancelled.');
});

<?php

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Enums\PaymentProviderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WebhookEventStatus;
use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Payments\FakePaymentGateway;

uses(RefreshDatabase::class);

function paymentsAddress(): array
{
    return [
        'line1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
    ];
}

function paymentsProduct(int $price = 5000, int $qty = 10): Product
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

function paymentsOrder(User $user, Product $product, int $qty = 2): Order
{
    $cart = Cart::factory()->create(['user_id' => $user->id]);
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => $qty,
        'unit_price' => $product->price->cents(),
    ]);

    test()->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', ['shipping_address' => paymentsAddress()])
        ->assertCreated();

    return Order::query()->first();
}

function fakeGateway(): FakePaymentGateway
{
    $fake = new FakePaymentGateway;
    app()->instance(PaymentGateway::class, $fake);

    return $fake;
}

function checkoutSessionEvent(string $type, array $object): object
{
    return (object) [
        'id' => 'evt_test_1',
        'type' => $type,
        'data' => (object) [
            'object' => (object) $object,
        ],
    ];
}

function webhookPayload(string $type, array $object = []): array
{
    $event = checkoutSessionEvent($type, array_merge(['id' => FakePaymentGateway::SESSION_ID], $object));

    return [
        'event' => $event,
        'body' => json_encode((object) ['id' => $event->id, 'type' => $event->type]),
    ];
}

function postWebhook(string $body, string $signature = 'valid-signature'): TestResponse
{
    return test()->call(
        'POST',
        '/api/v1/payments/webhook',
        server: ['HTTP_STRIPE_SIGNATURE' => $signature],
        content: $body,
    );
}

it('requires authentication to start checkout', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $this->postJson('/api/v1/payments/checkout', ['order_id' => $order->id])
        ->assertStatus(401);
});

it('creates a Stripe checkout session for a pending order', function (): void {
    $fake = fakeGateway();
    $user = User::factory()->create();
    $product = paymentsProduct();
    $order = paymentsOrder($user, $product, 2);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments/checkout', ['order_id' => $order->id])
        ->assertCreated()
        ->assertJsonPath('data.payment.provider', 'stripe')
        ->assertJsonPath('data.payment.provider_payment_id', FakePaymentGateway::SESSION_ID)
        ->assertJsonPath('data.payment.status', 'pending')
        ->assertJsonStructure(['data' => ['url']]);

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'provider' => 'stripe',
        'provider_payment_id' => FakePaymentGateway::SESSION_ID,
        'status' => PaymentProviderStatus::Pending->value,
        'amount' => $order->total->cents(),
    ]);

    expect($fake->createdSessions)->toHaveCount(1);
});

it('hides another users order during checkout', function (): void {
    fakeGateway();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments/checkout', ['order_id' => $order->id])
        ->assertStatus(404);
});

it('rejects checkout for an already paid order', function (): void {
    fakeGateway();
    $user = User::factory()->create();
    $product = paymentsProduct();
    $order = paymentsOrder($user, $product);

    $order->payments()->create([
        'provider' => 'stripe',
        'status' => PaymentProviderStatus::Succeeded,
        'amount' => $order->total->cents(),
        'currency' => $order->currency,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments/checkout', ['order_id' => $order->id])
        ->assertStatus(422);
});

it('rejects a webhook with an invalid signature', function (): void {
    $payload = json_encode(['id' => 'evt_test_1', 'type' => 'checkout.session.completed']);

    postWebhook($payload, 'bad-signature')
        ->assertStatus(400)
        ->assertJsonPath('message', 'Invalid webhook signature.');
});

it('confirms an order when the checkout session completes', function (): void {
    $fake = fakeGateway();
    $user = User::factory()->create();
    $product = paymentsProduct(5000, 10);
    $order = paymentsOrder($user, $product, 2);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments/checkout', ['order_id' => $order->id])
        ->assertCreated();

    $event = checkoutSessionEvent('checkout.session.completed', [
        'id' => FakePaymentGateway::SESSION_ID,
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_1',
    ]);

    $fake->nextEvent = $event;

    postWebhook(json_encode((object) ['id' => $event->id, 'type' => $event->type]))
        ->assertOk();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentProviderStatus::Succeeded)
        ->and($payment->provider_payment_id)->toBe('pi_test_1')
        ->and($payment->paid_at)->not->toBeNull();

    $inventory = $product->fresh()->inventory;
    expect($inventory->quantity)->toBe(8)
        ->and($inventory->reserved_quantity)->toBe(0);

    $this->assertDatabaseHas('inventory_transactions', [
        'type' => InventoryTransactionType::Sale->value,
        'quantity_change' => -2,
    ]);

    $this->assertDatabaseHas('webhook_events', [
        'event_id' => 'evt_test_1',
        'status' => WebhookEventStatus::Processed->value,
    ]);
});

it('handles duplicate webhook deliveries idempotently', function (): void {
    $fake = fakeGateway();
    $user = User::factory()->create();
    $product = paymentsProduct(5000, 10);
    $order = paymentsOrder($user, $product, 2);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments/checkout', ['order_id' => $order->id])
        ->assertCreated();

    $payload = webhookPayload('checkout.session.completed', ['payment_status' => 'paid', 'payment_intent' => 'pi_test_1']);
    $fake->nextEvent = $payload['event'];

    postWebhook($payload['body'])->assertOk();
    postWebhook($payload['body'])->assertOk();

    $inventory = $product->fresh()->inventory;
    expect($inventory->quantity)->toBe(8)
        ->and($inventory->reserved_quantity)->toBe(0);

    $this->assertDatabaseCount('webhook_events', 1);
});

it('releases stock when a checkout session expires', function (): void {
    $fake = fakeGateway();
    $user = User::factory()->create();
    $product = paymentsProduct(5000, 10);
    $order = paymentsOrder($user, $product, 2);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments/checkout', ['order_id' => $order->id])
        ->assertCreated();

    $payload = webhookPayload('checkout.session.expired');
    $fake->nextEvent = $payload['event'];

    postWebhook($payload['body'])
        ->assertOk();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Cancelled);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentProviderStatus::Failed);

    $inventory = $product->fresh()->inventory;
    expect($inventory->reserved_quantity)->toBe(0)
        ->and($inventory->quantity)->toBe(10);

    $this->assertDatabaseHas('inventory_transactions', [
        'type' => InventoryTransactionType::Release->value,
    ]);
});

it('ignores webhooks for unknown sessions', function (): void {
    $fake = fakeGateway();

    $event = checkoutSessionEvent('checkout.session.completed', [
        'id' => 'cs_unknown',
        'payment_status' => 'paid',
    ]);

    $fake->nextEvent = $event;

    postWebhook(json_encode((object) ['id' => $event->id, 'type' => $event->type]))
        ->assertOk();
});

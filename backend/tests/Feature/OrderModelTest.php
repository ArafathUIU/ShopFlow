<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('order money fields cast to Money value objects', function () {
    $order = Order::factory()->create([
        'subtotal' => 10000,
        'discount' => 1000,
        'tax' => 900,
        'shipping_fee' => 500,
        'total' => 10400,
    ]);

    expect($order->subtotal->cents())->toBe(10000)
        ->and($order->total->cents())->toBe(10400);
});

test('orders snapshot address and placed timestamps', function () {
    $order = Order::factory()->create(['placed_at' => now()]);

    expect($order->shipping_address)->toBeArray()
        ->and($order->billing_address)->toBeArray()
        ->and($order->placed_at)->not->toBeNull();
});

test('status transitions append to the audit trail', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $actor = User::factory()->create();

    $order->transitionTo(OrderStatus::Paid, 'Payment confirmed', $actor);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and(OrderStatusHistory::count())->toBe(1)
        ->and($order->statusHistory->first()->from_status)->toBe(OrderStatus::Pending)
        ->and($order->statusHistory->first()->to_status)->toBe(OrderStatus::Paid)
        ->and($order->statusHistory->first()->user_id)->toBe($actor->id);
});

test('order payment state helpers', function () {
    $pending = Order::factory()->create();
    $paid = Order::factory()->paid()->create();

    expect($pending->isPaid())->toBeFalse()
        ->and($pending->canBeCancelled())->toBeTrue()
        ->and($paid->isPaid())->toBeTrue()
        ->and($paid->payment_status)->toBe(PaymentStatus::Paid);
});

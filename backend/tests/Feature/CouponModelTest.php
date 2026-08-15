<?php

use App\Models\Coupon;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('percent coupon discount is a percentage of the subtotal', function () {
    $coupon = Coupon::factory()->percent(10)->create();

    expect($coupon->discountFor(Money::fromCents(10000))->cents())->toBe(1000);
});

test('fixed coupon discount is a flat amount', function () {
    $coupon = Coupon::factory()->fixed(2000)->create();

    expect($coupon->discountFor(Money::fromCents(10000))->cents())->toBe(2000);
});

test('discount never exceeds the subtotal', function () {
    $coupon = Coupon::factory()->fixed(5000)->create();

    expect($coupon->discountFor(Money::fromCents(3000))->cents())->toBe(3000);
});

test('min order amount gates the discount', function () {
    $coupon = Coupon::factory()->percent(10)->create(['min_order_amount' => 5000]);

    expect($coupon->discountFor(Money::fromCents(4999))->cents())->toBe(0)
        ->and($coupon->discountFor(Money::fromCents(5000))->cents())->toBe(500);
});

test('max discount amount caps the discount', function () {
    $coupon = Coupon::factory()->percent(50)->create(['max_discount_amount' => 1500]);

    expect($coupon->discountFor(Money::fromCents(10000))->cents())->toBe(1500);
});

test('expired coupons are not active', function () {
    $expired = Coupon::factory()->expired()->create();
    $active = Coupon::factory()->create();

    expect($expired->isActive())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue()
        ->and($active->isActive())->toBeTrue()
        ->and(Coupon::query()->active()->count())->toBe(1);
});

test('usage limits are enforced', function () {
    $coupon = Coupon::factory()->create(['usage_limit' => 5, 'per_user_limit' => 1, 'times_used' => 5]);
    $user = User::factory()->create();

    expect($coupon->hasReachedUsageLimit())->toBeTrue()
        ->and($coupon->hasReachedPerUserLimit($user))->toBeFalse();
});

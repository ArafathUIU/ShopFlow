<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('validates an applicable percentage coupon', function (): void {
    $coupon = Coupon::factory()->percent(10)->create(['code' => 'SAVE10']);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'SAVE10', 'subtotal' => 100.00])
        ->assertOk()
        ->assertJsonPath('data.valid', true)
        ->assertJsonPath('data.discount.cents', 1000)
        ->assertJsonPath('data.discount.formatted', '10.00');
});

it('validates a fixed amount coupon', function (): void {
    Coupon::factory()->fixed(2000)->create(['code' => 'FIX20']);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'FIX20', 'subtotal' => 100.00])
        ->assertOk()
        ->assertJsonPath('data.valid', true)
        ->assertJsonPath('data.discount.cents', 2000);
});

it('caps the discount at the subtotal', function (): void {
    Coupon::factory()->fixed(2000)->create(['code' => 'FIX20']);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'FIX20', 'subtotal' => 10.00])
        ->assertOk()
        ->assertJsonPath('data.discount.cents', 1000);
});

it('returns invalid for an unknown code', function (): void {
    $this->postJson('/api/v1/coupons/validate', ['code' => 'NOPE', 'subtotal' => 50.00])
        ->assertOk()
        ->assertJsonPath('data.valid', false)
        ->assertJsonPath('data.message', 'Coupon code not found.');
});

it('returns invalid for an expired coupon', function (): void {
    Coupon::factory()->expired()->create(['code' => 'OLDSALE']);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'OLDSALE', 'subtotal' => 50.00])
        ->assertOk()
        ->assertJsonPath('data.valid', false)
        ->assertJsonPath('data.message', 'This coupon is no longer valid.');
});

it('returns invalid when below the minimum order amount', function (): void {
    Coupon::factory()->create([
        'code' => 'BIGSAVE',
        'value' => 20,
        'min_order_amount' => 10000,
    ]);

    $this->postJson('/api/v1/coupons/validate', ['code' => 'BIGSAVE', 'subtotal' => 50.00])
        ->assertOk()
        ->assertJsonPath('data.valid', false);
});

it('returns invalid when the user has exhausted the per-user limit', function (): void {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create(['code' => 'ONCE', 'value' => 10, 'per_user_limit' => 1]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $coupon->usages()->create(['user_id' => $user->id, 'order_id' => $order->id]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/validate', ['code' => 'ONCE', 'subtotal' => 50.00])
        ->assertOk()
        ->assertJsonPath('data.valid', false)
        ->assertJsonPath('data.message', 'You have already used this coupon.');
});

it('returns invalid when the coupon reached its usage limit', function (): void {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create(['code' => 'LIMIT5', 'value' => 10, 'usage_limit' => 5]);
    $coupon->update(['times_used' => 5]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons/validate', ['code' => 'LIMIT5', 'subtotal' => 50.00])
        ->assertOk()
        ->assertJsonPath('data.valid', false)
        ->assertJsonPath('data.message', 'This coupon has reached its usage limit.');
});

it('rejects missing required input', function (): void {
    $this->postJson('/api/v1/coupons/validate', [])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['code']]);
});

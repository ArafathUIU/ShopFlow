<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??####')),
            'type' => CouponType::Percent,
            'value' => 10,
            'min_order_amount' => null,
            'max_discount_amount' => null,
            'usage_limit' => null,
            'per_user_limit' => null,
            'times_used' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function percent(int $value = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Percent,
            'value' => $value,
        ]);
    }

    public function fixed(int $cents = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Fixed,
            'value' => $cents,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(500, 200000);
        $discount = 0;
        $shipping = 500;
        $tax = intdiv($subtotal - $discount, 10);
        $total = $subtotal - $discount + $shipping + $tax;

        return [
            'order_number' => 'SF-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100000, 999999),
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping_fee' => $shipping,
            'total' => $total,
            'shipping_address' => [
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => fake()->postcode(),
                'country' => 'US',
            ],
            'billing_address' => [
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => fake()->postcode(),
                'country' => 'US',
            ],
            'placed_at' => now(),
        ];
    }

    public function withStatus(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
        ]);
    }
}

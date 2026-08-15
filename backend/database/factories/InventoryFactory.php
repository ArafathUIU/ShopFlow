<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(0, 500),
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
        ];
    }

    public function inStock(int $quantity = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    public function lowStock(int $quantity = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }
}

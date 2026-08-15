<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->numberBetween(199, 99999);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
            'price' => $price,
            'compare_at_price' => null,
            'status' => ProductStatus::Active,
            'is_featured' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Draft,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Archived,
            'archived_at' => now(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'compare_at_price' => intval($attributes['price'] * 1.2),
        ]);
    }
}

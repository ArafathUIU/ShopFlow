<?php

namespace Database\Factories;

use App\Enums\AddressType;
use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => AddressType::Shipping,
            'line1' => fake()->streetAddress(),
            'line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country' => 'US',
            'is_default' => false,
        ];
    }

    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AddressType::Shipping,
        ]);
    }

    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AddressType::Billing,
        ]);
    }
}

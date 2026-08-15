<?php

namespace Database\Seeders;

use App\Enums\AddressType;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@shopflow.dev'],
            [
                'name' => 'ShopFlow Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@shopflow.dev'],
            [
                'name' => 'ShopFlow Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::Manager,
                'email_verified_at' => now(),
            ]
        );

        $customer = User::query()->updateOrCreate(
            ['email' => 'customer@shopflow.dev'],
            [
                'name' => 'ShopFlow Customer',
                'password' => Hash::make('password'),
                'role' => UserRole::Customer,
                'email_verified_at' => now(),
            ]
        );

        foreach ([$admin, $manager, $customer] as $user) {
            Address::query()->updateOrCreate(
                ['user_id' => $user->id, 'type' => AddressType::Shipping],
                [
                    'line1' => '123 Market Street',
                    'city' => 'Springfield',
                    'state' => 'IL',
                    'postal_code' => '62701',
                    'country' => 'US',
                    'is_default' => true,
                ]
            );

            if ($user->isCustomer()) {
                Cart::query()->firstOrCreate(['user_id' => $user->id]);
            }
        }

        User::factory()->count(8)->create();
    }
}

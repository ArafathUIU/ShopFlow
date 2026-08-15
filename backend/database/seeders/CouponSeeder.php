<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'type' => CouponType::Percent,
                'value' => 10,
                'min_order_amount' => 5000,
                'max_discount_amount' => 2500,
                'per_user_limit' => 1,
            ],
            [
                'code' => 'SAVE20',
                'type' => CouponType::Fixed,
                'value' => 2000,
                'min_order_amount' => 10000,
                'expires_at' => now()->addDays(30),
            ],
            [
                'code' => 'TECHFEST',
                'type' => CouponType::Percent,
                'value' => 15,
                'min_order_amount' => 15000,
                'max_discount_amount' => 5000,
                'usage_limit' => 100,
                'expires_at' => now()->addDays(60),
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::query()->updateOrCreate(
                ['code' => $coupon['code']],
                array_merge([
                    'times_used' => 0,
                    'starts_at' => null,
                    'expires_at' => $coupon['expires_at'] ?? null,
                    'usage_limit' => $coupon['usage_limit'] ?? null,
                    'per_user_limit' => $coupon['per_user_limit'] ?? null,
                    'is_active' => true,
                ], $coupon)
            );
        }
    }
}

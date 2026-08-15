<?php

namespace App\Services\Coupons;

use App\Exceptions\InvalidCouponException;
use App\Models\Coupon;
use App\Models\User;
use App\Support\Money;

final class CouponService
{
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()->where('code', $code)->first();
    }

    /**
     * @throws InvalidCouponException
     */
    public function assertValid(Coupon $coupon, User $user, Money $subtotal): void
    {
        if (! $coupon->isActive()) {
            throw new InvalidCouponException('This coupon is no longer valid.');
        }

        if ($coupon->hasReachedUsageLimit()) {
            throw new InvalidCouponException('This coupon has reached its usage limit.');
        }

        if ($coupon->hasReachedPerUserLimit($user)) {
            throw new InvalidCouponException('You have already used this coupon.');
        }

        if ($coupon->min_order_amount !== null && $subtotal->cents() < $coupon->min_order_amount->cents()) {
            throw new InvalidCouponException(
                'This coupon requires a minimum order of $'.$coupon->min_order_amount->format().'.'
            );
        }
    }
}

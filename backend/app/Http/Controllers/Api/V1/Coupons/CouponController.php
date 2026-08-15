<?php

namespace App\Http\Controllers\Api\V1\Coupons;

use App\Http\Controllers\Controller;
use App\Services\Coupons\CouponService;
use App\Support\ApiResponse;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(private readonly CouponService $coupons) {}

    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $coupon = $this->coupons->findByCode($validated['code']);

        if (! $coupon) {
            return $this->invalidResponse('Coupon code not found.');
        }

        $subtotal = Money::fromAmount((float) ($validated['subtotal'] ?? 0));

        if (! $coupon->isActive()) {
            return $this->invalidResponse('This coupon is no longer valid.');
        }

        if ($coupon->hasReachedUsageLimit()) {
            return $this->invalidResponse('This coupon has reached its usage limit.');
        }

        if ($request->user() !== null && $coupon->hasReachedPerUserLimit($request->user())) {
            return $this->invalidResponse('You have already used this coupon.');
        }

        if ($coupon->min_order_amount !== null && $subtotal->cents() < $coupon->min_order_amount->cents()) {
            return $this->invalidResponse(
                'This coupon requires a minimum order of $'.$coupon->min_order_amount->format().'.'
            );
        }

        $discount = $coupon->discountFor($subtotal);

        return ApiResponse::success([
            'valid' => true,
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type->value,
            'value' => $coupon->value,
            'discount' => [
                'cents' => $discount->cents(),
                'formatted' => $discount->format(),
            ],
            'message' => 'Coupon is valid.',
        ]);
    }

    private function invalidResponse(string $message): JsonResponse
    {
        return ApiResponse::success([
            'valid' => false,
            'message' => $message,
        ]);
    }
}

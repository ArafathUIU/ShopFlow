<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtotal = $this->subtotal();
        $discount = $this->coupon !== null ? $this->coupon->discountFor($subtotal) : Money::zero();

        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'item_count' => $this->itemCount(),
            'coupon' => $this->whenLoaded('coupon', fn () => $this->coupon !== null ? [
                'id' => $this->coupon->id,
                'code' => $this->coupon->code,
                'discount' => [
                    'cents' => $discount->cents(),
                    'formatted' => $discount->format(),
                ],
            ] : null),
            'subtotal' => [
                'cents' => $subtotal->cents(),
                'formatted' => $subtotal->format(),
            ],
            'discount' => [
                'cents' => $discount->cents(),
                'formatted' => $discount->format(),
            ],
        ];
    }
}

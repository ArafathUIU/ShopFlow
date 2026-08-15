<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'currency' => $this->currency,
            'subtotal' => ['cents' => $this->subtotal->cents(), 'formatted' => $this->subtotal->format()],
            'discount' => ['cents' => $this->discount->cents(), 'formatted' => $this->discount->format()],
            'shipping_fee' => ['cents' => $this->shipping_fee->cents(), 'formatted' => $this->shipping_fee->format()],
            'tax' => ['cents' => $this->tax->cents(), 'formatted' => $this->tax->format()],
            'total' => ['cents' => $this->total->cents(), 'formatted' => $this->total->format()],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'customer_note' => $this->customer_note,
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
            'placed_at' => $this->placed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

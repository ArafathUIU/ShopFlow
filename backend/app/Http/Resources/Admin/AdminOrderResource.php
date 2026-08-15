<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone ?? null,
            ] : null,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'shipping_fee' => $this->shipping_fee,
            'total' => $this->total,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'customer_note' => $this->customer_note,
            'items_count' => $this->items_count ?? $this->items->count(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'price' => $item->product->price,
                ],
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'provider' => $payment->provider,
                'provider_transaction_id' => $payment->provider_transaction_id,
                'amount' => $payment->amount,
                'status' => $payment->status->value,
                'created_at' => $payment->created_at->toIso8601String(),
            ])),
            'status_history' => $this->whenLoaded('statusHistory', fn () => $this->statusHistory->map(fn ($history) => [
                'from_status' => $history->from_status->value,
                'to_status' => $history->to_status->value,
                'reason' => $history->reason,
                'changed_at' => $history->created_at->toIso8601String(),
            ])),
            'placed_at' => $this->placed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

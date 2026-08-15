<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'deactivated_at' => $this->deactivated_at?->toIso8601String(),
            'is_active' => is_null($this->deactivated_at),
            'stats' => [
                'total_orders' => $this->orders_count ?? 0,
                'total_spent' => $this->orders_sum_total ?? 0,
                'wishlist_items' => $this->wishlists_count ?? 0,
            ],
            'orders' => $this->whenLoaded('orders', fn () => $this->orders->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $order->total,
                'status' => $order->status->value,
                'placed_at' => $order->placed_at->toIso8601String(),
            ])),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

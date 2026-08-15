<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class AdminProductResource extends ProductResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'status' => $this->status->value,
            'is_active' => $this->isActive(),
            'archived_at' => $this->archived_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'inventory' => $this->whenLoaded('inventory', fn (): ?array => $this->inventory !== null ? [
                'quantity' => $this->inventory->quantity,
                'reserved_quantity' => $this->inventory->reserved_quantity,
                'available' => $this->inventory->availableQuantity(),
                'low_stock_threshold' => $this->inventory->low_stock_threshold,
                'is_low_stock' => $this->inventory->isLowStock(),
            ] : null),
        ]);
    }
}

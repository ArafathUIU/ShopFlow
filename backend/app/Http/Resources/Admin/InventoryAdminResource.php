<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $inventory = $this;

        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_slug' => $this->product->slug,
            'quantity' => $inventory->quantity,
            'reserved_quantity' => $inventory->reserved_quantity,
            'available_quantity' => $inventory->availableQuantity(),
            'low_stock_threshold' => $inventory->low_stock_threshold,
            'is_low_stock' => $inventory->isLowStock(),
            'is_out_of_stock' => $inventory->isOutOfStock(),
        ];
    }
}
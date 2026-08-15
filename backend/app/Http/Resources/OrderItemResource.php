<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'sku' => $this->sku,
            'unit_price' => [
                'cents' => $this->unit_price->cents(),
                'formatted' => $this->unit_price->format(),
            ],
            'quantity' => $this->quantity,
            'total' => [
                'cents' => $this->total->cents(),
                'formatted' => $this->total->format(),
            ],
        ];
    }
}

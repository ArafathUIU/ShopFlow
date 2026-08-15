<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lineTotal = $this->lineTotal();

        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => [
                'cents' => $this->unit_price->cents(),
                'formatted' => $this->unit_price->format(),
            ],
            'line_total' => [
                'cents' => $lineTotal->cents(),
                'formatted' => $lineTotal->format(),
            ],
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}

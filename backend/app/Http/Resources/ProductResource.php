<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $price = $this->price;
        $compareAt = $this->compare_at_price;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'is_featured' => $this->is_featured,
            'price' => [
                'cents' => $price->cents(),
                'formatted' => $price->format(),
            ],
            'compare_at_price' => $compareAt !== null
                ? ['cents' => $compareAt->cents(), 'formatted' => $compareAt->format()]
                : null,
            'is_on_sale' => $this->isOnSale(),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'images' => $this->whenLoaded('images', fn () => ProductImageResource::collection($this->images)),
            'primary_image' => $this->whenLoaded('primaryImage', fn () => new ProductImageResource($this->primaryImage)),
            'in_stock' => $this->whenLoaded('inventory', fn () => $this->inventory !== null && ! $this->inventory->isOutOfStock()),
            'available_quantity' => $this->whenLoaded('inventory', fn () => $this->inventory?->availableQuantity()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

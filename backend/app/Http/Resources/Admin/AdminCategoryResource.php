<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent->id,
                'name' => $this->parent->name,
                'slug' => $this->parent->slug,
            ]),
            'children_count' => $this->whenLoaded('children', fn () => $this->children->count()),
            'products_count' => $this->whenLoaded('products', fn () => $this->products->count()),
        ];
    }
}
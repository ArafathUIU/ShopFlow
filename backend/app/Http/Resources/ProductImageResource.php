<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $url = $this->path;

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return [
                'id' => $this->id,
                'path' => $this->path,
                'url' => $url,
                'alt_text' => $this->alt_text,
                'sort_order' => $this->sort_order,
                'is_primary' => $this->is_primary,
            ];
        }

        return [
            'id' => $this->id,
            'path' => $this->path,
            'url' => Storage::disk($this->disk)->url($this->path),
            'alt_text' => $this->alt_text,
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,
        ];
    }
}

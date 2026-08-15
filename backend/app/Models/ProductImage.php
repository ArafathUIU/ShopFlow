<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'path', 'disk', 'alt_text', 'sort_order', 'is_primary'])]
class ProductImage extends Model
{
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Ensure a product has at most one primary image.
     */
    protected static function booted(): void
    {
        static::saving(function (ProductImage $image): void {
            if (! $image->is_primary) {
                return;
            }

            static::query()
                ->where('product_id', $image->product_id)
                ->where('id', '!=', $image->id)
                ->update(['is_primary' => false]);
        });
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId)->orderBy('sort_order');
    }
}

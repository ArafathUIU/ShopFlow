<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id', 'quantity', 'reserved_quantity', 'low_stock_threshold'])]
class Inventory extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Units currently available for purchase.
     */
    public function availableQuantity(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function hasSufficient(int $quantity): bool
    {
        return $this->availableQuantity() >= $quantity;
    }

    public function isOutOfStock(): bool
    {
        return $this->availableQuantity() <= 0;
    }

    public function isLowStock(): bool
    {
        return $this->availableQuantity() <= $this->low_stock_threshold;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'low_stock_threshold');
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'reserved_quantity');
    }
}

<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ProductStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id',
    'name',
    'slug',
    'description',
    'sku',
    'price',
    'compare_at_price',
    'status',
    'is_featured',
    'archived_at',
])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class,
            'compare_at_price' => MoneyCast::class,
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Products available for sale on the storefront.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', ProductStatus::Active)
            ->whereNull('archived_at');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInCategory(Builder $query, int|Category $category): Builder
    {
        return $query->where('category_id', $category instanceof Category ? $category->id : $category);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->whereLike('name', "%{$term}%")
                ->orWhereLike('description', "%{$term}%")
                ->orWhereLike('sku', "%{$term}%");
        });
    }

    public function scopePriceBetween(Builder $query, ?int $min, ?int $max): Builder
    {
        return $query
            ->when($min !== null, fn (Builder $q) => $q->where('price', '>=', $min))
            ->when($max !== null, fn (Builder $q) => $q->where('price', '<=', $max));
    }

    public function scopeWithStock(Builder $query): Builder
    {
        return $query->whereHas('inventory', function (Builder $q): void {
            $q->whereColumn('quantity', '>', 'reserved_quantity');
        });
    }

    public function isActive(): bool
    {
        return $this->status->isActive() && $this->archived_at === null;
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null
            && $this->compare_at_price->isPositive()
            && $this->compare_at_price->cents() > $this->price->cents();
    }

    public function saleAmount(): ?Money
    {
        if (! $this->isOnSale()) {
            return null;
        }

        return $this->compare_at_price->subtract($this->price);
    }
}

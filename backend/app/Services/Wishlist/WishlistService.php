<?php

namespace App\Services\Wishlist;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Collection;

final class WishlistService
{
    public function itemsFor(User $user): Collection
    {
        return WishlistItem::query()
            ->with(['product.primaryImage', 'product.inventory', 'product.category'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function productIdsFor(User $user): array
    {
        return WishlistItem::query()
            ->where('user_id', $user->id)
            ->pluck('product_id')
            ->all();
    }

    /**
     * @return array{WishlistItem, bool} item and whether it was just created.
     */
    public function add(User $user, Product $product): array
    {
        $exists = $user->wishlistItems()->where('product_id', $product->id)->exists();

        $item = $user->wishlistItems()->firstOrCreate(['product_id' => $product->id]);

        return [$item, ! $exists];
    }

    public function remove(User $user, Product $product): bool
    {
        return (bool) $user->wishlistItems()->where('product_id', $product->id)->delete();
    }
}

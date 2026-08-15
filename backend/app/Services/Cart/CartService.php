<?php

namespace App\Services\Cart;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidCouponException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;

final class CartService
{
    public function getOrCreateFor(User $user): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(Cart $cart, Product $product, int $quantity): CartItem
    {
        $item = $cart->items()->where('product_id', $product->id)->first();

        $newQuantity = $item ? $item->quantity + $quantity : $quantity;

        $this->assertAvailable($product, $newQuantity);

        if ($item) {
            $item->update([
                'quantity' => $newQuantity,
                'unit_price' => $product->price->cents(),
            ]);

            return $item;
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $product->price->cents(),
        ]);
    }

    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        $this->assertAvailable($item->product, $quantity);

        $item->update([
            'quantity' => $quantity,
            'unit_price' => $item->product->price->cents(),
        ]);

        return $item;
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_id' => null]);
    }

    public function applyCoupon(Cart $cart, Coupon $coupon, User $user): Cart
    {
        if (! $coupon->isActive()) {
            throw new InvalidCouponException('This coupon is no longer valid.');
        }

        if ($coupon->hasReachedUsageLimit()) {
            throw new InvalidCouponException('This coupon has reached its usage limit.');
        }

        if ($coupon->hasReachedPerUserLimit($user)) {
            throw new InvalidCouponException('You have already used this coupon.');
        }

        $subtotal = $cart->subtotal();

        if ($coupon->min_order_amount !== null && $subtotal->cents() < $coupon->min_order_amount->cents()) {
            throw new InvalidCouponException(
                'This coupon requires a minimum order of $'.$coupon->min_order_amount->format().'.'
            );
        }

        $cart->update(['coupon_id' => $coupon->id]);

        return $cart;
    }

    public function removeCoupon(Cart $cart): Cart
    {
        $cart->update(['coupon_id' => null]);

        return $cart;
    }

    private function assertAvailable(Product $product, int $quantity): void
    {
        $available = $product->inventory?->availableQuantity() ?? 0;

        if ($quantity > $available) {
            throw InsufficientStockException::forProduct($available, $product->name);
        }
    }
}

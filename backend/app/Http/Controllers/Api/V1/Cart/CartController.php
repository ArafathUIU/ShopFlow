<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\ApplyCouponRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cart
            ->getOrCreateFor($request->user())
            ->load(['items.product.primaryImage', 'items.product.inventory', 'items.product.category', 'coupon']);

        return ApiResponse::success(new CartResource($cart));
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $product = Product::query()->with('inventory')->findOrFail($request->integer('product_id'));

        if (! $product->isActive()) {
            return ApiResponse::error('This product is no longer available.', 422);
        }

        $cart = $this->cart->getOrCreateFor($request->user());

        $item = $this->cart->addItem($cart, $product, $request->integer('quantity'));

        return ApiResponse::success(
            new CartItemResource($item->load(['product.primaryImage', 'product.inventory', 'product.category'])),
            'Item added to cart.',
            201,
        );
    }

    public function updateItem(UpdateCartItemRequest $request, CartItem $item): JsonResponse
    {
        $this->authorizeOwnership($request, $item);

        $item = $this->cart->updateItem($item->load('product.inventory'), $request->integer('quantity'));

        return ApiResponse::success(
            new CartItemResource($item->load(['product.primaryImage', 'product.inventory', 'product.category'])),
            'Cart item updated.',
        );
    }

    public function removeItem(Request $request, CartItem $item): JsonResponse
    {
        $this->authorizeOwnership($request, $item);

        $this->cart->removeItem($item);

        return ApiResponse::noContent('Item removed from cart.');
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cart->getOrCreateFor($request->user());

        $this->cart->clear($cart);

        return ApiResponse::noContent('Cart cleared.');
    }

    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        $coupon = Coupon::query()->where('code', $request->string('code'))->first();

        if (! $coupon) {
            return ApiResponse::error('Coupon code not found.', 422);
        }

        $cart = $this->cart->getOrCreateFor($request->user());

        $this->cart->applyCoupon($cart, $coupon, $request->user());

        return ApiResponse::success(
            new CartResource($cart->fresh(['items.product.primaryImage', 'items.product.inventory', 'items.product.category', 'coupon'])),
            'Coupon applied.',
        );
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->cart->getOrCreateFor($request->user());

        $this->cart->removeCoupon($cart);

        return ApiResponse::success(
            new CartResource($cart->load(['items.product.primaryImage', 'items.product.inventory', 'items.product.category', 'coupon'])),
            'Coupon removed.',
        );
    }

    private function authorizeOwnership(Request $request, CartItem $item): void
    {
        $cartId = $this->cart->getOrCreateFor($request->user())->id;

        abort_unless($item->cart_id === $cartId, 404, 'Cart item not found.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Wishlist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wishlist\AddWishlistItemRequest;
use App\Http\Resources\WishlistItemResource;
use App\Models\Product;
use App\Services\Wishlist\WishlistService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlist) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            WishlistItemResource::collection($this->wishlist->itemsFor($request->user())),
            'OK',
            200,
            ['product_ids' => $this->wishlist->productIdsFor($request->user())],
        );
    }

    public function store(AddWishlistItemRequest $request): JsonResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        [$item, $created] = $this->wishlist->add($request->user(), $product);

        return ApiResponse::success(
            new WishlistItemResource($item->load(['product.primaryImage', 'product.inventory', 'product.category'])),
            $created ? 'Added to wishlist.' : 'Already in wishlist.',
            $created ? 201 : 200,
        );
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->wishlist->remove($request->user(), $product);

        return ApiResponse::noContent('Removed from wishlist.');
    }
}

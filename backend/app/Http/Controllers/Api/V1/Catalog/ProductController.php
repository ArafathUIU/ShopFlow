<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\IndexProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(IndexProductsRequest $request): JsonResponse
    {
        $sort = $request->input('sort');

        $query = Product::query()
            ->active()
            ->with(['category', 'primaryImage', 'images', 'inventory'])
            ->when($request->filled('search'), fn (Builder $q) => $q->search($request->string('search')))
            ->when($request->filled('category'), fn (Builder $q) => $q->inCategory($request->integer('category')))
            ->when($request->filled('min_price'), fn (Builder $q) => $q->where('price', '>=', $request->minPriceCents()))
            ->when($request->filled('max_price'), fn (Builder $q) => $q->where('price', '<=', $request->maxPriceCents()));

        if ($sort === 'popular') {
            $query->withCount('orderItems');
        }

        match ($sort) {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'name_asc' => $query->orderBy('name')->orderBy('id'),
            'name_desc' => $query->orderByDesc('name')->orderByDesc('id'),
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'featured' => $query->orderByDesc('is_featured')->orderByDesc('created_at')->orderByDesc('id'),
            'popular' => $query->orderByDesc('order_items_count')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $products = $query->paginate($request->perPage());

        return ApiResponse::success(
            ProductResource::collection($products),
            'OK',
            200,
            ['pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ]],
        );
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->active()
            ->where('slug', $slug)
            ->with(['category', 'primaryImage', 'images' => fn ($q) => $q->orderBy('sort_order'), 'inventory'])
            ->first();

        if (! $product) {
            return ApiResponse::error('Product not found.', 404);
        }

        return ApiResponse::success(new ProductResource($product));
    }
}

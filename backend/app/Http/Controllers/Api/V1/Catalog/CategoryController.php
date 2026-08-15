<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->whereNull('parent_id')
            ->withCount('products')
            ->with(['children' => function ($q): void {
                $q->active()->orderBy('sort_order')->withCount('products');
            }])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(CategoryResource::collection($categories));
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::query()
            ->active()
            ->where('slug', $slug)
            ->withCount('products')
            ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')->withCount('products')])
            ->first();

        if (! $category) {
            return ApiResponse::error('Category not found.', 404);
        }

        $products = Product::query()
            ->active()
            ->where('category_id', $category->id)
            ->with(['primaryImage', 'inventory'])
            ->orderByDesc('is_featured')
            ->latest()
            ->paginate(24);

        return ApiResponse::success(
            [
                'category' => new CategoryResource($category),
                'products' => ProductResource::collection($products),
            ],
            'OK',
            200,
            ['pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ]],
        );
    }
}

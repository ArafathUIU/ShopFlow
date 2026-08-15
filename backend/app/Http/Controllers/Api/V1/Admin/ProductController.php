<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\AttachImageRequest;
use App\Http\Requests\Admin\Products\IndexAdminProductsRequest;
use App\Http\Requests\Admin\Products\StoreProductRequest;
use App\Http\Requests\Admin\Products\UpdateProductRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private function findProduct(int $id): Product
    {
        return Product::withTrashed()->findOrFail($id);
    }

    public function index(IndexAdminProductsRequest $request): JsonResponse
    {
        $products = Product::query()
            ->withTrashed()
            ->with(['category', 'primaryImage', 'inventory'])
            ->when($request->filled('search'), fn (Builder $q) => $q->search($request->string('search')))
            ->when($request->filled('category'), fn (Builder $q) => $q->inCategory($request->integer('category')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')))
            ->when($request->boolean('trashed_only'), fn (Builder $q) => $q->onlyTrashed())
            ->latest('updated_at')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return ApiResponse::success(
            AdminProductResource::collection($products),
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

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create([
            'category_id' => $request->filled('category_id') ? $request->integer('category_id') : null,
            'name' => $request->string('name')->toString(),
            'slug' => $request->filled('slug')
                ? $request->string('slug')->toString()
                : Str::slug($request->string('name')),
            'description' => $request->string('description')->value() ?: null,
            'sku' => $request->string('sku')->toString(),
            'price' => $request->priceCents(),
            'compare_at_price' => $request->compareAtPriceCents(),
            'status' => ProductStatus::from($request->input('status', ProductStatus::Draft->value)),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        foreach ($request->input('images', []) as $image) {
            $product->images()->create([
                'path' => $image['path'],
                'disk' => $image['disk'] ?? 'public',
                'alt_text' => $image['alt_text'] ?? null,
                'sort_order' => $image['sort_order'] ?? 0,
                'is_primary' => $image['is_primary'] ?? false,
            ]);
        }

        return ApiResponse::created(
            new AdminProductResource($product->load(['category', 'images', 'inventory'])),
            'Product created.',
        );
    }

    public function show(int $product): JsonResponse
    {
        $product = $this->findProduct($product)->load(['category', 'images' => fn ($q) => $q->orderBy('sort_order'), 'inventory']);

        return ApiResponse::success(new AdminProductResource($product));
    }

    public function update(UpdateProductRequest $request, int $product): JsonResponse
    {
        $product = $this->findProduct($product);

        $product->update([
            'category_id' => $request->filled('category_id') ? $request->integer('category_id') : $product->category_id,
            'name' => $request->filled('name') ? $request->string('name')->toString() : $product->name,
            'slug' => $request->filled('slug') ? $request->string('slug')->toString() : $product->slug,
            'description' => $request->has('description')
                ? ($request->string('description')->value() ?: null)
                : $product->description,
            'sku' => $request->filled('sku') ? $request->string('sku')->toString() : $product->sku,
            'price' => $request->filled('price') ? $request->priceCents() : $product->price->cents(),
            'compare_at_price' => $request->has('compare_at_price')
                ? $request->compareAtPriceCents()
                : ($product->compare_at_price?->cents()),
            'status' => ProductStatus::from($request->filled('status') ? $request->input('status') : $product->status->value),
            'is_featured' => $request->has('is_featured') ? $request->boolean('is_featured') : $product->is_featured,
        ]);

        return ApiResponse::success(
            new AdminProductResource($product->load(['category', 'images', 'inventory'])),
            'Product updated.',
        );
    }

    public function destroy(int $product): JsonResponse
    {
        $product = $this->findProduct($product);
        $product->delete();

        return ApiResponse::success([], 'Product deleted.');
    }

    public function restore(int $product): JsonResponse
    {
        $product = $this->findProduct($product);
        $product->restore();

        return ApiResponse::success(
            new AdminProductResource($product->load(['category', 'images', 'inventory'])),
            'Product restored.',
        );
    }

    public function archive(int $product): JsonResponse
    {
        $product = $this->findProduct($product);
        $product->update([
            'status' => ProductStatus::Archived,
            'archived_at' => now(),
        ]);

        return ApiResponse::success(new AdminProductResource($product->fresh(['category', 'images', 'inventory'])), 'Product archived.');
    }

    public function unarchive(int $product): JsonResponse
    {
        $product = $this->findProduct($product);
        $product->update([
            'status' => ProductStatus::Active,
            'archived_at' => null,
        ]);

        return ApiResponse::success(new AdminProductResource($product->fresh(['category', 'images', 'inventory'])), 'Product unarchived.');
    }

    public function attachImage(AttachImageRequest $request, int $product): JsonResponse
    {
        $product = $this->findProduct($product);

        $image = $product->images()->create([
            'path' => $request->string('path')->toString(),
            'disk' => $request->string('disk', 'public')->toString(),
            'alt_text' => $request->string('alt_text')->value() ?: null,
            'sort_order' => $request->integer('sort_order', 0),
            'is_primary' => $request->boolean('is_primary'),
        ]);

        return ApiResponse::created(new ProductImageResource($image), 'Image attached.');
    }

    public function detachImage(int $product, int $image): JsonResponse
    {
        $product = $this->findProduct($product);

        $product->images()->findOrFail($image)->delete();

        return ApiResponse::success([], 'Image removed.');
    }
}

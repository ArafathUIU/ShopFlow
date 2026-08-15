<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Categories\IndexAdminCategoriesRequest;
use App\Http\Requests\Admin\Categories\StoreCategoryRequest;
use App\Http\Requests\Admin\Categories\UpdateCategoryRequest;
use App\Http\Resources\Admin\AdminCategoryResource;
use App\Models\Category;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    private function findCategory(int $id): Category
    {
        return Category::withTrashed()->findOrFail($id);
    }

    public function index(IndexAdminCategoriesRequest $request): JsonResponse
    {
        $categories = Category::query()
            ->withTrashed()
            ->with(['parent', 'children', 'products'])
            ->when($request->filled('search'), fn (Builder $q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('is_active'), fn (Builder $q) => $q->where('is_active', filter_var($request->string('is_active'), FILTER_VALIDATE_BOOLEAN)))
            ->when($request->filled('parent_id'), fn (Builder $q) => $q->where('parent_id', $request->integer('parent_id')))
            ->latest('updated_at')
            ->paginate($request->perPage());

        return ApiResponse::success(
            AdminCategoryResource::collection($categories),
            'OK',
            200,
            ['pagination' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ]],
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create([
            'name' => $request->string('name')->toString(),
            'slug' => $request->filled('slug') ? $request->string('slug')->toString() : Str::slug($request->string('name')),
            'description' => $request->string('description')->value() ?: null,
            'parent_id' => $request->filled('parent_id') ? $request->integer('parent_id') : null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return ApiResponse::created(
            new AdminCategoryResource($category->load(['parent', 'children', 'products'])),
            'Category created.',
        );
    }

    public function show(int $category): JsonResponse
    {
        $category = $this->findCategory($category)->load(['parent', 'children', 'products']);

        return ApiResponse::success(new AdminCategoryResource($category));
    }

    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        $category = $this->findCategory($category);

        $category->update([
            'name' => $request->filled('name') ? $request->string('name')->toString() : $category->name,
            'slug' => $request->filled('slug') ? $request->string('slug')->toString() : $category->slug,
            'description' => $request->has('description')
                ? ($request->string('description')->value() ?: null)
                : $category->description,
            'parent_id' => $request->filled('parent_id') ? $request->integer('parent_id') : $category->parent_id,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $category->is_active,
            'sort_order' => $request->has('sort_order') ? $request->integer('sort_order') : $category->sort_order,
        ]);

        return ApiResponse::success(
            new AdminCategoryResource($category->load(['parent', 'children', 'products'])),
            'Category updated.',
        );
    }

    public function destroy(int $category): JsonResponse
    {
        $category = $this->findCategory($category);
        $category->is_active = false;
        $category->save();

        return ApiResponse::success(
            new AdminCategoryResource($category->fresh(['parent', 'children', 'products'])),
            'Category deactivated.',
        );
    }

    public function activate(int $category): JsonResponse
    {
        $category = $this->findCategory($category);
        $category->is_active = true;
        $category->save();

        return ApiResponse::success(
            new AdminCategoryResource($category->fresh(['parent', 'children', 'products'])),
            'Category activated.',
        );
    }

    public function deactivate(int $category): JsonResponse
    {
        $category = $this->findCategory($category);
        $category->is_active = false;
        $category->save();

        return ApiResponse::success(
            new AdminCategoryResource($category->fresh(['parent', 'children', 'products'])),
            'Category deactivated.',
        );
    }
}

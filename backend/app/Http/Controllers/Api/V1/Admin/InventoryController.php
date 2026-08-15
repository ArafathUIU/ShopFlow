<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\AdjustInventoryRequest;
use App\Http\Resources\Admin\InventoryAdminResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    private function findProduct(int $id): Product
    {
        return Product::withTrashed()->findOrFail($id);
    }

    private function findInventory(int $productId): Inventory
    {
        $inventory = Inventory::where('product_id', $productId)->first();

        if (! $inventory) {
            $inventory = Inventory::query()->create([
                'product_id' => $productId,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'low_stock_threshold' => 3,
            ]);
        }

        return $inventory;
    }

    public function index(): JsonResponse
    {
        $query = Inventory::query()
            ->with(['product' => fn ($q) => $q->select(['id', 'name', 'slug'])]);

        if (request()->boolean('low_stock')) {
            $query->lowStock();
        }

        if (request()->boolean('out_of_stock')) {
            $query->outOfStock();
        }

        if (request()->filled('product_id')) {
            $query->where('product_id', request()->integer('product_id'));
        }

        if (request()->filled('search')) {
            $search = '%' . request()->string('search') . '%';
            $query->whereHas('product', fn ($q) => $q->where('name', 'like', $search)
                ->orWhere('sku', 'like', $search));
        }

        $inventory = $query->latest('updated_at')->paginate(24);

        return ApiResponse::success(
            InventoryAdminResource::collection($inventory),
            'OK',
            200,
            ['pagination' => [
                'current_page' => $inventory->currentPage(),
                'per_page' => $inventory->perPage(),
                'total' => $inventory->total(),
                'last_page' => $inventory->lastPage(),
                'from' => $inventory->firstItem(),
                'to' => $inventory->lastItem(),
            ]],
        );
    }

    public function adjust(AdjustInventoryRequest $request, int $productId): JsonResponse
    {
        $product = $this->findProduct($productId);
        $inventory = $this->findInventory($productId);

        $quantity = $request->integer('quantity');
        $reason = $request->string('reason')->toString();

        $inventory->quantity = max(0, $inventory->quantity + $quantity);
        $inventory->save();

        // Record the adjustment as an inventory transaction (optional — we have InventoryTransaction model but not needed for basic adjust)

        $inventory->load(['product' => fn ($q) => $q->select(['id', 'name', 'slug'])]);

        return ApiResponse::success(
            new InventoryAdminResource($inventory),
            'Inventory adjusted.',
        );
    }
}
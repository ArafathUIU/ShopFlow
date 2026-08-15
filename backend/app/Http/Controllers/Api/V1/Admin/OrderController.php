<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\UpdateOrderStatusRequest;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $query = Order::query()
            ->with([
                'user' => fn ($q) => $q->select(['id', 'name', 'email']),
                'items.product' => fn ($q) => $q->select(['id', 'name', 'sku']),
                'payments' => fn ($q) => $q->select(['id', 'order_id', 'provider', 'provider_transaction_id', 'amount', 'status']),
            ])
            ->withCount(['items']);

        // Filter by status
        if (request()->filled('status')) {
            $status = request()->string('status');
            if (OrderStatus::tryFrom($status)) {
                $query->byStatus(OrderStatus::from($status));
            }
        }

        // Filter by payment status
        if (request()->filled('payment_status')) {
            $paymentStatus = request()->string('payment_status');
            $query->where('payment_status', $paymentStatus);
        }

        // Filter by date range
        if (request()->filled('from_date') || request()->filled('to_date')) {
            $query->between(
                request()->string('from_date'),
                request()->string('to_date')
            );
        }

        // Filter by user
        if (request()->filled('user_id')) {
            $query->where('user_id', request()->integer('user_id'));
        }

        // Filter by order number
        if (request()->filled('search')) {
            $search = '%' . request()->string('search') . '%';
            $query->where('order_number', 'like', $search)
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search));
        }

        $orders = $query->latest('placed_at')->paginate(24);

        return ApiResponse::success(
            AdminOrderResource::collection($orders),
            'OK',
            200,
            ['pagination' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ]],
        );
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'user' => fn ($q) => $q->select(['id', 'name', 'email', 'phone']),
            'items.product' => fn ($q) => $q->select(['id', 'name', 'sku', 'price']),
            'payments' => fn ($q) => $q->select(['id', 'order_id', 'provider', 'provider_transaction_id', 'amount', 'status', 'created_at']),
            'statusHistory' => fn ($q) => $q->latest('created_at'),
        ]);

        return ApiResponse::success(
            new AdminOrderResource($order),
            'Order details retrieved.',
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $newStatus = OrderStatus::from($request->string('status'));
        $oldStatus = $order->status;

        // Validate state transition
        $validTransitions = [
            OrderStatus::Pending => [OrderStatus::Paid, OrderStatus::Cancelled],
            OrderStatus::Paid => [OrderStatus::Processing, OrderStatus::Cancelled],
            OrderStatus::Processing => [OrderStatus::Shipped, OrderStatus::Cancelled],
            OrderStatus::Shipped => [OrderStatus::Delivered],
            OrderStatus::Cancelled => [],
            OrderStatus::Delivered => [],
        ];

        if (! in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            return ApiResponse::error(
                "Cannot transition from {$oldStatus->value} to {$newStatus->value}",
                'INVALID_STATE_TRANSITION',
                422,
            );
        }

        $order->status = $newStatus;
        $order->save();

        // Record status history
        $order->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by_user_id' => auth()->id(),
            'reason' => $request->string('reason', ''),
        ]);

        $order->load([
            'user' => fn ($q) => $q->select(['id', 'name', 'email']),
            'items.product' => fn ($q) => $q->select(['id', 'name', 'sku']),
            'statusHistory' => fn ($q) => $q->latest('created_at'),
        ]);

        return ApiResponse::success(
            new AdminOrderResource($order),
            'Order status updated.',
        );
    }

    public function cancel(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        if (! $order->canBeCancelled()) {
            return ApiResponse::error(
                'This order cannot be cancelled in its current status.',
                'ORDER_NOT_CANCELLABLE',
                422,
            );
        }

        $order->status = OrderStatus::Cancelled;
        $order->save();

        // Record cancellation in status history
        $order->statusHistory()->create([
            'from_status' => $order->status,
            'to_status' => OrderStatus::Cancelled,
            'changed_by_user_id' => auth()->id(),
            'reason' => $request->string('reason', 'Cancelled by admin'),
        ]);

        // Release reserved inventory
        foreach ($order->items as $item) {
            $inventory = $item->product->inventory;
            if ($inventory) {
                $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $item->quantity);
                $inventory->save();
            }
        }

        $order->load([
            'user' => fn ($q) => $q->select(['id', 'name', 'email']),
            'items.product' => fn ($q) => $q->select(['id', 'name', 'sku']),
        ]);

        return ApiResponse::success(
            new AdminOrderResource($order),
            'Order cancelled and inventory released.',
        );
    }
}

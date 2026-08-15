<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $billingAddress = $request->input('billing_address') ?? $request->input('shipping_address');

        $order = $this->orders->placeOrderFromCart(
            $request->user(),
            $request->input('shipping_address'),
            $billingAddress,
            $request->string('customer_note')->value() ?: null,
        );

        return ApiResponse::created(
            new OrderResource($order->load(['items', 'payments', 'statusHistory'])),
            'Order placed.',
        );
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->forUser($request->user())
            ->with(['items', 'payments'])
            ->latest()
            ->paginate(20);

        return ApiResponse::success(
            OrderResource::collection($orders),
            'OK',
            200,
            ['pagination' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ]],
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404, 'Order not found.');

        return ApiResponse::success(new OrderResource($order->load(['items', 'payments', 'statusHistory'])));
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404, 'Order not found.');

        $order = $this->orders->cancel($order, $request->user());

        return ApiResponse::success(
            new OrderResource($order->load(['items', 'payments', 'statusHistory'])),
            'Order cancelled.',
        );
    }
}

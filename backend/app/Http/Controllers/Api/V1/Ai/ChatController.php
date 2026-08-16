<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chat(): JsonResponse
    {
        $message = strtolower(trim(request()->input('message', '')));

        if (empty($message)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => 'Hello! I can help you find products, check your orders, or answer questions about our store. What would you like to know?',
                ],
            ]);
        }

        $user = request()->user();

        if (str_contains($message, 'order') || str_contains($message, 'status')) {
            if ($user) {
                $orders = Order::query()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->limit(5)
                    ->get(['order_number', 'status', 'total', 'placed_at']);

                if ($orders->isNotEmpty()) {
                    $orderList = $orders->map(fn ($o) => sprintf(
                        '- %s: %s (placed %s)',
                        $o->order_number,
                        ucfirst($o->status->value),
                        $o->placed_at->format('M d, Y')
                    ))->join("\n");

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'reply' => "Here are your recent orders:\n{$orderList}\n\nYou can also ask about a specific order number.",
                        ],
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => 'I can help you check your order status. Please log in to view your orders, or provide your order number for details.',
                ],
            ]);
        }

        if (str_contains($message, 'product') || str_contains($message, 'find') || str_contains($message, 'search')) {
            $query = Product::query()->active();

            if (preg_match('/\b(laptop|phone|headphone|watch|camera|speaker|tablet)\b/i', $message, $matches)) {
                $query->where('name', 'like', '%'.$matches[1].'%');
            } elseif (strlen($message) > 2) {
                $query->where('name', 'like', '%'.$message.'%')
                    ->orWhere('description', 'like', '%'.$message.'%');
            }

            $products = $query->with(['primaryImage', 'inventory'])
                ->limit(5)
                ->get();

            if ($products->isNotEmpty()) {
                $productList = $products->map(fn ($p) => sprintf(
                    '- %s: $%s %s',
                    $p->name,
                    $p->price->format(),
                    $p->compare_at_price ? '(On Sale!)' : ''
                ))->join("\n");

                return response()->json([
                    'success' => true,
                    'data' => [
                        'reply' => "Here are some products I found:\n{$productList}\n\nWould you like more details on any of these?",
                        'products' => ProductResource::collection($products),
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => 'I couldn\'t find any products matching your search. Try browsing our categories or using different keywords.',
                ],
            ]);
        }

        if (str_contains($message, 'shipping') || str_contains($message, 'delivery')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => 'We offer free shipping on orders over $100. Standard shipping takes 3-5 business days. Express shipping (1-2 business days) is available for $15. All orders are tracked and insured.',
                ],
            ]);
        }

        if (str_contains($message, 'return') || str_contains($message, 'refund')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => 'We have a 30-day return policy. Items must be in original condition with tags attached. To initiate a return, go to your order details and click "Request Return". Refunds are processed within 5-7 business days.',
                ],
            ]);
        }

        if (str_contains($message, 'hello') || str_contains($message, 'hi') || str_contains($message, 'hey')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => 'Hello! Welcome to ShopFlow. I can help you find products, check your orders, or answer questions about shipping and returns. What would you like to know?',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reply' => 'I\'m not sure how to help with that yet. I can assist you with:\n- Finding products\n- Checking order status\n- Shipping information\n- Return and refund policies\n\nWhat would you like to know?',
            ],
        ]);
    }
}

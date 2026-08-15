<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $toDate = now()->endOfDay();
        $fromDate = now()->subDays(30)->startOfDay();

        $data = [
            'revenue' => $this->getRevenue($fromDate, $toDate),
            'orders' => $this->getOrderStats($fromDate, $toDate),
            'products' => $this->getProductStats(),
            'customers' => $this->getCustomerStats(),
            'top_products' => $this->getTopProducts(5),
            'recent_orders' => $this->getRecentOrders(5),
        ];

        return ApiResponse::success($data, 'Analytics dashboard retrieved.');
    }

    public function revenue(?string $from = null, ?string $to = null): JsonResponse
    {
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subMonths(12)->startOfDay();

        $revenueData = Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereBetween('placed_at', [$fromDate, $toDate])
            ->select(
                DB::raw('DATE_TRUNC(\'day\', placed_at) AS date'),
                DB::raw('SUM(total) AS total_revenue'),
                DB::raw('COUNT(*) AS order_count')
            )
            ->groupBy(DB::raw('DATE_TRUNC(\'day\', placed_at)'))
            ->orderBy('date')
            ->get();

        $totalRevenue = Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereBetween('placed_at', [$fromDate, $toDate])
            ->sum('total');

        return ApiResponse::success([
            'period' => [
                'from' => $fromDate->toIso8601String(),
                'to' => $toDate->toIso8601String(),
            ],
            'total_revenue' => $totalRevenue,
            'daily_revenue' => $revenueData->map(fn ($row) => [
                'date' => $row->date,
                'revenue' => $row->total_revenue,
                'order_count' => $row->order_count,
            ]),
        ]);
    }

    public function products(): JsonResponse
    {
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::active()->count(),
            'archived_products' => Product::archived()->count(),
            'low_stock_products' => Product::with('inventory')
                ->get()
                ->filter(fn ($p) => $p->inventory?->quantity <= $p->inventory?->low_stock_threshold)
                ->count(),
            'out_of_stock_products' => Product::with('inventory')
                ->get()
                ->filter(fn ($p) => $p->inventory?->quantity == 0)
                ->count(),
        ];

        return ApiResponse::success($stats);
    }

    public function orders(?string $from = null, ?string $to = null): JsonResponse
    {
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subMonths(12)->startOfDay();

        $statusBreakdown = Order::query()
            ->whereBetween('placed_at', [$fromDate, $toDate])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status->value => [
                'count' => $row->count,
                'revenue' => $row->revenue,
            ]]);

        return ApiResponse::success([
            'period' => [
                'from' => $fromDate->toIso8601String(),
                'to' => $toDate->toIso8601String(),
            ],
            'total_orders' => Order::whereBetween('placed_at', [$fromDate, $toDate])->count(),
            'status_breakdown' => $statusBreakdown,
        ]);
    }

    private function getRevenue(Carbon $fromDate, Carbon $toDate): array
    {
        $totalRevenue = Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereBetween('placed_at', [$fromDate, $toDate])
            ->sum('total');

        $previousTotal = Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereBetween('placed_at', [$fromDate->copy()->subDays(30), $fromDate->copy()->subSecond()])
            ->sum('total');

        $percentageChange = $previousTotal > 0 ? (($totalRevenue - $previousTotal) / $previousTotal) * 100 : 0;

        return [
            'total' => $totalRevenue,
            'previous_period' => $previousTotal,
            'percentage_change' => round($percentageChange, 2),
        ];
    }

    private function getOrderStats(Carbon $fromDate, Carbon $toDate): array
    {
        $totalOrders = Order::whereBetween('placed_at', [$fromDate, $toDate])->count();
        $paidOrders = Order::where('status', OrderStatus::Paid)->whereBetween('placed_at', [$fromDate, $toDate])->count();
        $cancelledOrders = Order::where('status', OrderStatus::Cancelled)->whereBetween('placed_at', [$fromDate, $toDate])->count();

        return [
            'total' => $totalOrders,
            'paid' => $paidOrders,
            'cancelled' => $cancelledOrders,
            'average_order_value' => $totalOrders > 0 ? Order::whereBetween('placed_at', [$fromDate, $toDate])->avg('total') : 0,
        ];
    }

    private function getProductStats(): array
    {
        return [
            'total' => Product::count(),
            'active' => Product::active()->count(),
            'low_stock' => Product::with('inventory')
                ->get()
                ->filter(fn ($p) => $p->inventory && $p->inventory->quantity <= $p->inventory->low_stock_threshold)
                ->count(),
        ];
    }

    private function getCustomerStats(): array
    {
        return [
            'total' => User::where('role', 'customer')->count(),
            'active_this_month' => User::where('role', 'customer')
                ->whereHas('orders', fn ($q) => $q->whereBetween('placed_at', [now()->subMonth(), now()]))
                ->count(),
            'new_this_month' => User::where('role', 'customer')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];
    }

    private function getTopProducts(int $limit = 5): array
    {
        return OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total) as total_revenue'))
            ->with('product:id,name,sku')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                ],
                'quantity_sold' => $item->total_quantity,
                'revenue' => $item->total_revenue,
            ])
            ->toArray();
    }

    private function getRecentOrders(int $limit = 5): array
    {
        return Order::query()
            ->select('id', 'order_number', 'status', 'total', 'placed_at')
            ->latest('placed_at')
            ->limit($limit)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'total' => $order->total,
                'placed_at' => $order->placed_at->toIso8601String(),
            ])
            ->toArray();
    }
}

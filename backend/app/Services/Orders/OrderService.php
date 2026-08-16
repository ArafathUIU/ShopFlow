<?php

namespace App\Services\Orders;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidCheckoutException;
use App\Jobs\ProcessAnalyticsEvent;
use App\Jobs\SendOrderConfirmationNotification;
use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OrderService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly PricingService $pricing,
    ) {}

    public function placeOrderFromCart(User $user, array $shippingAddress, array $billingAddress, ?string $note = null): Order
    {
        $cart = $this->cartService->getOrCreateFor($user)->load(['items.product.inventory', 'coupon']);

        if ($cart->isEmpty()) {
            throw new InvalidCheckoutException('Your cart is empty.');
        }

        foreach ($cart->items as $item) {
            if (! $item->product?->isActive()) {
                throw new InvalidCheckoutException('Some items in your cart are no longer available.');
            }
        }

        return DB::transaction(function () use ($user, $cart, $shippingAddress, $billingAddress, $note): Order {
            $productIds = $cart->items->pluck('product_id')->sort()->values();

            $inventories = Inventory::query()
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $subtotal = $cart->subtotal();
            $discount = $cart->coupon !== null ? $cart->coupon->discountFor($subtotal) : Money::zero();
            $taxable = $subtotal->subtract($discount);
            $shippingFee = $this->pricing->shippingFor($subtotal);
            $tax = $this->pricing->taxFor($taxable);
            $total = $taxable->add($shippingFee)->add($tax);

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
                'currency' => 'USD',
                'subtotal' => $subtotal->cents(),
                'discount' => $discount->cents(),
                'tax' => $tax->cents(),
                'shipping_fee' => $shippingFee->cents(),
                'total' => $total->cents(),
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
                'customer_note' => $note,
                'placed_at' => now(),
            ]);

            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'note' => 'Order placed',
                'user_id' => $user->id,
            ]);

            foreach ($cart->items as $item) {
                $inventory = $inventories->get($item->product_id);

                if ($inventory === null || ! $inventory->hasSufficient($item->quantity)) {
                    throw InsufficientStockException::forProduct($inventory?->availableQuantity() ?? 0, $item->product->name);
                }
            }

            foreach ($cart->items as $item) {
                $inventory = $inventories->get($item->product_id);
                $availableBefore = $inventory->availableQuantity();

                $inventory->reserved_quantity += $item->quantity;
                $inventory->save();

                $inventory->transactions()->create([
                    'user_id' => $user->id,
                    'quantity_change' => -$item->quantity,
                    'quantity_before' => $availableBefore,
                    'quantity_after' => $availableBefore - $item->quantity,
                    'type' => InventoryTransactionType::Reservation,
                    'reason' => 'Order reservation',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                ]);

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'unit_price' => $item->unit_price->cents(),
                    'quantity' => $item->quantity,
                    'total' => $item->lineTotal()->cents(),
                ]);
            }

            if ($cart->coupon !== null) {
                $cart->coupon->increment('times_used');

                $order->couponUsages()->create([
                    'coupon_id' => $cart->coupon->id,
                    'user_id' => $user->id,
                ]);
            }

            $this->cartService->clear($cart);

            SendOrderConfirmationNotification::dispatch($order->load('user'));
            ProcessAnalyticsEvent::dispatch('order.placed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $order->total,
                'user_id' => $user->id,
                'items_count' => $order->items()->count(),
            ]);

            return $order;
        }        );
    }

    /**
     * Convert reservations into real decrements once payment succeeds.
     */
    public function confirmPayment(Order $order): void
    {
        if (! $order->isPaid()) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $order->load('items');

            $inventories = Inventory::query()
                ->whereIn('product_id', $order->items->pluck('product_id'))
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($order->items as $item) {
                $inventory = $inventories->get($item->product_id);

                if ($inventory === null) {
                    continue;
                }

                $decrement = min($inventory->reserved_quantity, $item->quantity);

                $inventory->quantity = max(0, $inventory->quantity - $decrement);
                $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $decrement);
                $inventory->save();

                $inventory->transactions()->create([
                    'user_id' => $order->user_id,
                    'quantity_change' => -$decrement,
                    'quantity_before' => $inventory->quantity + $decrement,
                    'quantity_after' => $inventory->quantity,
                    'type' => InventoryTransactionType::Sale,
                    'reason' => 'Payment confirmed',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                ]);
            }
        });
    }

    /**
     * Cancel an order and release (unpaid) or return (paid) stock.
     */
    public function cancel(Order $order, ?User $actor = null): Order
    {
        if (! $order->canBeCancelled()) {
            throw new InvalidCheckoutException('This order cannot be cancelled.');
        }

        $order->load('items', 'couponUsages');

        return DB::transaction(function () use ($order, $actor): Order {
            $inventories = Inventory::query()
                ->whereIn('product_id', $order->items->pluck('product_id'))
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($order->items as $item) {
                $inventory = $inventories->get($item->product_id);

                if ($inventory === null) {
                    continue;
                }

                $type = $order->isPaid() ? InventoryTransactionType::Return : InventoryTransactionType::Release;
                $before = $inventory->availableQuantity();

                if ($order->isPaid()) {
                    $inventory->quantity += $item->quantity;
                } else {
                    $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $item->quantity);
                }

                $inventory->save();

                $inventory->transactions()->create([
                    'user_id' => $order->user_id,
                    'quantity_change' => $item->quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $inventory->availableQuantity(),
                    'type' => $type,
                    'reason' => $order->isPaid() ? 'Order refunded' : 'Order cancelled',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                ]);
            }

            $order->transitionTo(OrderStatus::Cancelled, 'Order cancelled', $actor);

            foreach ($order->couponUsages as $usage) {
                $usage->coupon?->decrement('times_used');
            }

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        return 'SF-'.now()->format('Ym').'-'.strtoupper(Str::random(6));
    }
}

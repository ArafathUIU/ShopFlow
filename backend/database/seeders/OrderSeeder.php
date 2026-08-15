<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentProviderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::query()->where('email', 'customer@shopflow.dev')->first();

        if ($customer === null) {
            return;
        }

        $products = Product::query()->active()->with('inventory')->get();

        if ($products->isEmpty()) {
            return;
        }

        $cycles = [
            ['status' => OrderStatus::Delivered, 'payment' => PaymentStatus::Paid, 'months' => 3],
            ['status' => OrderStatus::Shipped, 'payment' => PaymentStatus::Paid, 'months' => 2],
            ['status' => OrderStatus::Delivered, 'payment' => PaymentStatus::Paid, 'months' => 2],
            ['status' => OrderStatus::Processing, 'payment' => PaymentStatus::Paid, 'months' => 1],
            ['status' => OrderStatus::Delivered, 'payment' => PaymentStatus::Paid, 'months' => 1],
            ['status' => OrderStatus::Shipped, 'payment' => PaymentStatus::Paid, 'days' => 12],
            ['status' => OrderStatus::Delivered, 'payment' => PaymentStatus::Paid, 'days' => 8],
            ['status' => OrderStatus::Processing, 'payment' => PaymentStatus::Paid, 'days' => 4],
            ['status' => OrderStatus::Pending, 'payment' => PaymentStatus::Pending, 'days' => 1],
            ['status' => OrderStatus::Cancelled, 'payment' => PaymentStatus::Refunded, 'days' => 20],
        ];

        foreach ($cycles as $index => $cycle) {
            $item = $products->random();

            $placedAt = now()->subMonths($cycle['months'] ?? 0)->subDays($cycle['days'] ?? 0);

            $quantity = fake()->numberBetween(1, 3);
            $subtotal = $item->price->multiply($quantity);
            $shipping = 500;
            $tax = intdiv($subtotal->cents(), 10);
            $total = $subtotal->cents() + $shipping + $tax;
            $orderNumber = sprintf('SF-%s-%05d', now()->format('Y'), $index + 1);

            $order = Order::query()->updateOrCreate(
                ['order_number' => $orderNumber],
                [
                    'user_id' => $customer->id,
                    'status' => $cycle['status'],
                    'payment_status' => $cycle['payment'],
                    'currency' => 'USD',
                    'subtotal' => $subtotal->cents(),
                    'discount' => 0,
                    'tax' => $tax,
                    'shipping_fee' => $shipping,
                    'total' => $total,
                    'shipping_address' => [
                        'line1' => '123 Market Street',
                        'city' => 'Springfield',
                        'state' => 'IL',
                        'postal_code' => '62701',
                        'country' => 'US',
                    ],
                    'billing_address' => [
                        'line1' => '123 Market Street',
                        'city' => 'Springfield',
                        'state' => 'IL',
                        'postal_code' => '62701',
                        'country' => 'US',
                    ],
                    'placed_at' => $placedAt,
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ]
            );

            $order->items()->create([
                'product_id' => $item->id,
                'product_name' => $item->name,
                'sku' => $item->sku,
                'unit_price' => $item->price->cents(),
                'quantity' => $quantity,
                'total' => $subtotal->cents(),
            ]);

            if ($cycle['payment']->isPaid()) {
                $order->payments()->create([
                    'provider' => 'stripe',
                    'provider_payment_id' => 'pi_seed_'.str($order->order_number)->lower().'_'.$order->id,
                    'amount' => $total,
                    'currency' => 'USD',
                    'status' => PaymentProviderStatus::Succeeded,
                    'paid_at' => $placedAt,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => OrderStatus::Pending,
                'to_status' => $cycle['status'],
                'note' => 'Seeded demo order.',
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);
        }
    }
}

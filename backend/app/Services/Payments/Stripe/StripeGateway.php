<?php

namespace App\Services\Payments\Stripe;

use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use Stripe\StripeClient;
use Stripe\Webhook;

final class StripeGateway implements PaymentGateway
{
    private readonly string $secretKey;

    private ?StripeClient $client = null;

    public function __construct(?string $secretKey = null)
    {
        $this->secretKey = $secretKey ?? (string) config('services.stripe.secret');
    }

    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient($this->secretKey);
    }

    /**
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        $order->load('items');

        $lineItems = [];

        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $order->currency,
                    'unit_amount' => $item->unit_price->cents(),
                    'product_data' => ['name' => $item->product_name],
                ],
                'quantity' => $item->quantity,
            ];
        }

        if ($order->shipping_fee->isPositive()) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $order->currency,
                    'unit_amount' => $order->shipping_fee->cents(),
                    'product_data' => ['name' => 'Shipping'],
                ],
                'quantity' => 1,
            ];
        }

        if ($order->tax->isPositive()) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $order->currency,
                    'unit_amount' => $order->tax->cents(),
                    'product_data' => ['name' => 'Tax'],
                ],
                'quantity' => 1,
            ];
        }

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $order->user->email,
            'client_reference_id' => $order->order_number,
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'expires_at' => now()->addHours(2)->timestamp,
        ]);

        return [
            'id' => $session->id,
            'url' => $session->url,
        ];
    }

    public function parseWebhookEvent(string $payload, string $signature, string $secret): object
    {
        return Webhook::constructEvent($payload, $signature, $secret);
    }
}

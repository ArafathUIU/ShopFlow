<?php

namespace App\Services\Payments;

use App\Models\Order;
use Stripe\Exception\SignatureVerificationException;

interface PaymentGateway
{
    /**
     * Create a hosted checkout session for an order.
     *
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array;

    /**
     * Verify and parse a webhook payload.
     *
     * @throws SignatureVerificationException
     */
    public function parseWebhookEvent(string $payload, string $signature, string $secret): object;
}

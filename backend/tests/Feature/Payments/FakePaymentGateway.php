<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use Stripe\Exception\SignatureVerificationException;

class FakePaymentGateway implements PaymentGateway
{
    public const SESSION_ID = 'cs_test_1';

    public object $nextEvent;

    /** @var array<int, array{order: Order, success_url: string, cancel_url: string}> */
    public array $createdSessions = [];

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        $this->createdSessions[] = [
            'order' => $order,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ];

        return [
            'id' => self::SESSION_ID,
            'url' => 'https://checkout.stripe.com/test/'.self::SESSION_ID,
        ];
    }

    public function parseWebhookEvent(string $payload, string $signature, string $secret): object
    {
        if ($signature !== 'valid-signature') {
            throw new SignatureVerificationException('Invalid signature.');
        }

        return $this->nextEvent;
    }
}

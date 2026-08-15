<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentProviderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WebhookEventStatus;
use App\Exceptions\InvalidCheckoutException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Orders\OrderService;
use Throwable;

final class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly OrderService $orders,
    ) {}

    public function gateway(): PaymentGateway
    {
        return $this->gateway;
    }

    /**
     * @return array{payment: Payment, url: string}
     */
    public function createCheckoutFor(Order $order, User $user): array
    {
        if ($order->user_id !== $user->id) {
            throw new InvalidCheckoutException('This order does not belong to you.');
        }

        if ($order->status !== OrderStatus::Pending) {
            throw new InvalidCheckoutException('This order can no longer be paid.');
        }

        if ($order->payments()->where('status', PaymentProviderStatus::Succeeded)->exists()) {
            throw new InvalidCheckoutException('This order has already been paid.');
        }

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        $result = $this->gateway->createCheckoutSession(
            $order,
            $frontendUrl.'/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            $frontendUrl.'/checkout/cancel',
        );

        $payment = $order->payments()->create([
            'provider' => 'stripe',
            'provider_payment_id' => $result['id'],
            'amount' => $order->total->cents(),
            'currency' => $order->currency,
            'status' => PaymentProviderStatus::Pending,
        ]);

        return ['payment' => $payment, 'url' => $result['url']];
    }

    /**
     * Handle an authenticated webhook event (already signature-verified).
     */
    public function handleWebhook(object $event): void
    {
        $webhook = WebhookEvent::query()->firstOrCreate(
            ['provider' => 'stripe', 'event_id' => $event->id],
            [
                'event_type' => $event->type,
                'payload' => json_decode(json_encode($event->data->object ?? new \stdClass), true) ?: [],
                'status' => WebhookEventStatus::Received,
            ],
        );

        $webhook->markProcessing();

        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
                'checkout.session.expired' => $this->handleCheckoutExpired($event->data->object),
                default => null,
            };

            $webhook->markProcessed();
        } catch (Throwable $e) {
            $webhook->markFailed();

            throw $e;
        }
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $payment = Payment::query()->where('provider_payment_id', $session->id)->first();

        if ($payment === null || $payment->status->isSucceeded()) {
            return;
        }

        $order = $payment->order;

        if (($session->payment_status ?? null) !== 'paid') {
            return;
        }

        $payment->markSucceeded($session->payment_intent ?? null, json_decode(json_encode($session), true) ?: []);

        $order->payment_status = PaymentStatus::Paid;
        $order->save();

        $order->transitionTo(OrderStatus::Paid, 'Payment confirmed via Stripe');

        $this->orders->confirmPayment($order);
    }

    private function handleCheckoutExpired(object $session): void
    {
        $payment = Payment::query()->where('provider_payment_id', $session->id)->first();

        if ($payment === null) {
            return;
        }

        $order = $payment->order;

        if ($order->status === OrderStatus::Pending && $payment->status === PaymentProviderStatus::Pending) {
            $this->orders->cancel($order);

            $payment->status = PaymentProviderStatus::Failed;
            $payment->save();
        }
    }
}

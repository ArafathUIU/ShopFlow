<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Exceptions\InvalidCheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\CreateCheckoutRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\Payments\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function checkout(CreateCheckoutRequest $request): JsonResponse
    {
        $order = Order::query()->findOrFail($request->integer('order_id'));

        abort_unless($order->user_id === $request->user()->id, 404, 'Order not found.');

        try {
            ['payment' => $payment, 'url' => $url] = $this->payments->createCheckoutFor($order, $request->user());
        } catch (InvalidCheckoutException $e) {
            throw $e;
        } catch (Throwable $e) {
            return ApiResponse::error('Unable to start checkout at this time.', 502);
        }

        return ApiResponse::created([
            'payment' => new PaymentResource($payment),
            'url' => $url,
        ], 'Checkout started.');
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = (string) config('services.stripe.webhook_secret');

        if ($signature === null || $secret === '') {
            return ApiResponse::error('Missing webhook signature.', 400);
        }

        try {
            $event = $this->payments->gateway()->parseWebhookEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException) {
            return ApiResponse::error('Invalid webhook signature.', 400);
        }

        $this->payments->handleWebhook($event);

        return ApiResponse::success([], 'Webhook received.', 200);
    }
}

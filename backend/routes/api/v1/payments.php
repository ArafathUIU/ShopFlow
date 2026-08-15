<?php

use App\Http\Controllers\Api\V1\Payments\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/payments/checkout', [PaymentController::class, 'checkout']);
});

// Stripe webhook — public (Stripe authenticates via signature header).
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])
    ->withoutMiddleware('auth:sanctum');

<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentProviderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'provider',
    'provider_payment_id',
    'amount',
    'currency',
    'status',
    'paid_at',
    'raw_payload',
])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'status' => PaymentProviderStatus::class,
            'paid_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function markSucceeded(?string $providerPaymentId = null, ?array $payload = null): void
    {
        $this->provider_payment_id = $providerPaymentId ?? $this->provider_payment_id;
        $this->status = PaymentProviderStatus::Succeeded;
        $this->paid_at = $this->paid_at ?? now();
        if ($payload !== null) {
            $this->raw_payload = $payload;
        }
        $this->save();
    }
}

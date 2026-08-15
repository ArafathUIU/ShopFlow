<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'provider_payment_id' => $this->provider_payment_id,
            'amount' => ['cents' => $this->amount->cents(), 'formatted' => $this->amount->format()],
            'currency' => $this->currency,
            'status' => $this->status->value,
            'paid_at' => $this->paid_at?->toISOString(),
        ];
    }
}

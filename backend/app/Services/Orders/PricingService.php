<?php

namespace App\Services\Orders;

use App\Support\Money;

final class PricingService
{
    public function __construct(
        private readonly int $taxBasisPoints,
        private readonly int $freeShippingThresholdCents,
        private readonly int $flatShippingCents,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (int) round((float) config('shopflow.tax_rate') * 10000),
            (int) round((float) config('shopflow.free_shipping_threshold') * 100),
            (int) round((float) config('shopflow.flat_shipping') * 100),
        );
    }

    public function shippingFor(Money $subtotal): Money
    {
        if ($subtotal->cents() >= $this->freeShippingThresholdCents) {
            return Money::zero();
        }

        return Money::fromCents($this->flatShippingCents);
    }

    public function taxFor(Money $taxable): Money
    {
        if ($taxable->isZero()) {
            return Money::zero();
        }

        return Money::fromCents(intdiv($taxable->cents() * $this->taxBasisPoints, 10000));
    }
}

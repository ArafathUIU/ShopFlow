<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Immutable money value stored as integer minor units (cents).
 *
 * All monetary amounts in ShopFlow are stored as INTEGER cents in PostgreSQL
 * (see docs/database/schema.md). This value object wraps those amounts and
 * provides safe arithmetic and formatting.
 */
final class Money
{
    public function __construct(private readonly int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Create from a decimal amount (e.g. "24.99").
     */
    public static function fromAmount(float|int|string $amount): self
    {
        $normalized = rtrim(rtrim(number_format((float) $amount, 2, '.', ''), '0'), '.');

        return new self((int) (round((float) $normalized * 100)));
    }

    public function cents(): int
    {
        return $this->cents;
    }

    /**
     * Decimal amount, e.g. 24.99.
     */
    public function amount(): float
    {
        return $this->cents / 100;
    }

    /**
     * Formatted amount, e.g. "$1,234.56".
     */
    public function format(string $currency = 'USD'): string
    {
        return number_format($this->amount(), 2);
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self(max(0, $this->cents - $other->cents));
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function __toString(): string
    {
        return (string) $this->cents;
    }
}

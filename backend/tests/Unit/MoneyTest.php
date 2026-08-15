<?php

use App\Support\Money;

test('money stores integer cents', function () {
    $money = Money::fromCents(2499);

    expect($money->cents())->toBe(2499)
        ->and($money->amount())->toBe(24.99);
});

test('money can be created from a decimal amount', function () {
    expect(Money::fromAmount('24.99')->cents())->toBe(2499)
        ->and(Money::fromAmount(24.99)->cents())->toBe(2499)
        ->and(Money::fromAmount('0.10')->cents())->toBe(10);
});

test('money rejects negative values', function () {
    expect(fn () => Money::fromCents(-1))->toThrow(InvalidArgumentException::class);
});

test('money arithmetic', function () {
    $a = Money::fromCents(1500);
    $b = Money::fromCents(499);

    expect($a->add($b)->cents())->toBe(1999)
        ->and($a->subtract($b)->cents())->toBe(1001)
        ->and($b->subtract($a)->cents())->toBe(0)
        ->and($a->multiply(3)->cents())->toBe(4500);
});

test('money equality and formatting', function () {
    expect(Money::fromCents(2499)->equals(Money::fromCents(2499)))->toBeTrue()
        ->and(Money::fromCents(2499)->equals(Money::fromCents(2500)))->toBeFalse()
        ->and(Money::fromCents(123456)->format())->toBe('1,234.56')
        ->and(Money::zero()->isZero())->toBeTrue()
        ->and(Money::fromCents(1)->isPositive())->toBeTrue();
});

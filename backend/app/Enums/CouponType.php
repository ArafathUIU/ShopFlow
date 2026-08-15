<?php

namespace App\Enums;

enum CouponType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function isPercent(): bool
    {
        return $this === self::Percent;
    }
}

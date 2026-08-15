<?php

namespace App\Enums;

/**
 * Mirrors the payments.status column.
 */
enum PaymentProviderStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function isSucceeded(): bool
    {
        return $this === self::Succeeded;
    }
}

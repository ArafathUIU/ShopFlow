<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forProduct(int $available, string $productName): self
    {
        return new self("Only {$available} units of \"{$productName}\" are available.");
    }
}

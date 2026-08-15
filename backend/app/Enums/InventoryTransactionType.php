<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case Reservation = 'reservation';
    case Release = 'release';
    case Return = 'return';
    case Cancellation = 'cancellation';
}

<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cart_id', 'product_id', 'quantity', 'unit_price'])]
class CartItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => MoneyCast::class,
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): Money
    {
        return $this->unit_price->multiply($this->quantity);
    }
}

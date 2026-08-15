<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'inventory_id',
    'user_id',
    'quantity_change',
    'quantity_before',
    'quantity_after',
    'type',
    'reason',
    'reference_type',
    'reference_id',
])]
class InventoryTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'type' => InventoryTransactionType::class,
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number',
    'user_id',
    'status',
    'payment_status',
    'currency',
    'subtotal',
    'discount',
    'tax',
    'shipping_fee',
    'total',
    'shipping_address',
    'billing_address',
    'customer_note',
    'placed_at',
])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => MoneyCast::class,
            'discount' => MoneyCast::class,
            'tax' => MoneyCast::class,
            'shipping_fee' => MoneyCast::class,
            'total' => MoneyCast::class,
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeByStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from !== null, fn (Builder $q) => $q->whereDate('placed_at', '>=', $from))
            ->when($to !== null, fn (Builder $q) => $q->whereDate('placed_at', '<=', $to));
    }

    public function isPaid(): bool
    {
        return $this->payment_status->isPaid();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::Paid], true);
    }

    /**
     * Record a status transition in the audit trail.
     */
    public function transitionTo(OrderStatus $to, ?string $note = null, ?User $actor = null): void
    {
        $from = $this->status;

        $this->statusHistory()->create([
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'user_id' => $actor?->id,
        ]);

        $this->status = $to;
        $this->save();
    }
}

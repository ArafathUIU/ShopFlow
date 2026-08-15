<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CouponType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'type',
    'value',
    'min_order_amount',
    'max_discount_amount',
    'usage_limit',
    'per_user_limit',
    'times_used',
    'starts_at',
    'expires_at',
    'is_active',
])]
class Coupon extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'min_order_amount' => MoneyCast::class,
            'max_discount_amount' => MoneyCast::class,
            'usage_limit' => 'integer',
            'per_user_limit' => 'integer',
            'times_used' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function isActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return ! $this->isActive();
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit !== null && $this->times_used >= $this->usage_limit;
    }

    public function userUsageCount(User $user): int
    {
        return $this->usages()->where('user_id', $user->id)->count();
    }

    public function hasReachedPerUserLimit(User $user): bool
    {
        return $this->per_user_limit !== null
            && $this->userUsageCount($user) >= $this->per_user_limit;
    }

    /**
     * Compute the discount for a given subtotal.
     */
    public function discountFor(Money $subtotal): Money
    {
        if ($subtotal->isZero()) {
            return Money::zero();
        }

        if ($this->min_order_amount !== null && $subtotal->cents() < $this->min_order_amount->cents()) {
            return Money::zero();
        }

        $discount = $this->type->isPercent()
            ? Money::fromCents(intdiv($subtotal->cents() * $this->value, 100))
            : Money::fromCents($this->value);

        if ($discount->cents() > $subtotal->cents()) {
            $discount = $subtotal;
        }

        if ($this->max_discount_amount !== null && $discount->cents() > $this->max_discount_amount->cents()) {
            $discount = $this->max_discount_amount;
        }

        return $discount;
    }
}

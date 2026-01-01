<?php

namespace App\Domains\Pricing\Models;

use BcMath\Number;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $priceable_type
 * @property int $priceable_id
 * @property string $currency
 * @property int $amount
 * @property float $vat_rate
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_to
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|\Eloquent $priceable
 * @method static Builder<static>|Price active()
 * @method static Builder<static>|Price byCurrency(string $currency)
 * @method static Builder<static>|Price newModelQuery()
 * @method static Builder<static>|Price newQuery()
 * @method static Builder<static>|Price query()
 * @method static Builder<static>|Price validAt(?\Illuminate\Support\Carbon $at = null)
 * @method static Builder<static>|Price whereAmount($value)
 * @method static Builder<static>|Price whereCreatedAt($value)
 * @method static Builder<static>|Price whereCurrency($value)
 * @method static Builder<static>|Price whereId($value)
 * @method static Builder<static>|Price whereIsActive($value)
 * @method static Builder<static>|Price wherePriceableId($value)
 * @method static Builder<static>|Price wherePriceableType($value)
 * @method static Builder<static>|Price whereUpdatedAt($value)
 * @method static Builder<static>|Price whereValidFrom($value)
 * @method static Builder<static>|Price whereValidTo($value)
 * @method static Builder<static>|Price whereVatRate($value)
 * @mixin \Eloquent
 */
class Price extends Model
{
    protected $fillable = [
        'priceable_type',
        'priceable_id',
        'currency',
        'amount',
        'vat_rate',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'integer',
        'vat_rate' => 'float',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    /**
    * Polymorphic relation to priaceable models
    * (ProductVariant, ShippingMethod, etc.)
    */
    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
    * Scope: only active prices
    */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
    * Scope: prices valid at a given date (default: now)
    */
    public function scopeValidAt(Builder $query, ?Carbon $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where(function ($q) use ($at) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $at);
            })
            ->where(function ($q) use ($at) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $at);
            });
    }

    /**
     * Scope: by currency
     */
    public function scopeByCurrency(Builder $query, string $currency): Builder
    {
        return $query->where('currency', strtoupper($currency));
    }

    /**
    * Get gross amout (net + VAT)
    */
    public function grossAmount(): int
    {
        if ($this->vat_rate <= 0) {
            return $this->amount;
        }

        return (int) round(
            $this->amount * (1 + $this->vat_rate / 100)
        );
    }

    /**
     * Get VAT amount
     */
    public function vatAmount(): int
    {
        if ($this->vat_rate <= 0) {
            return 0;
        }

        return $this->grossAmount() - $this->amount;
    }

    /**
     * Format amount with currency symbol
     */
    public function formatted(): string
    {
        $value = number_format($this->amount / 100, 2);
        return "{$this->currency} {$value}";
    }

    /**
     * Check if price is currently valid
     */
    public function isCurrentlyValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_to && $this->valid_to->isPast()) {
            return false;
        }

        return true;
    }
}

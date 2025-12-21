<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Price extends Model
{
    protected $fillable = [
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
}

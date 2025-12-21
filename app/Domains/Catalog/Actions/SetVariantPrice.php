<?php

namespace App\Domains\Catalog\Actions;

use App\Models\Price;
use App\Models\ProductVariant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SetVariantPrice
{
    public function execute(
        ProductVariant $variant,
        string $currency,
        int $amount,
        float $vatRate,
        ?Carbon $validFrom = null,
        ?Carbon $validTo = null
    ): Price {
        return DB::transaction(function () use (
            $variant,
            $currency,
            $amount,
            $vatRate,
            $validFrom,
            $validTo
        ) {
            $now = now();

            /**
            * Deactivate overlapping active prices
            */
            $variant->prices()
                ->where('currency', $currency)
                ->where('is_active', true)
                ->where(function ($query) use ($validFrom, $validTo, $now) {
                    $from = $validFrom ?? $now;
                    $to = $validTo;

                    $query
                        ->whereNull('valid_to')
                        ->orWhere('valid_to', '>=', $from);

                    if ($to) {
                        $query->where(function ($q) use ($to) {
                            $q->whereNull('valid_from')
                                ->orWhere('valid_from', '<=', $to);
                        });
                    }
                })
                ->update([
                    'is_active' => false,
                    'valid_to' => $validFrom ?? $now,
                ]);

            /**
            * Create new active price
            */
            return $variant->prices()->create([
                'currency' => strtoupper($currency),
                'amount' => $amount,
                'vat_rate' => $vatRate,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'is_active' => true,    
            ]);
        });
    }
}

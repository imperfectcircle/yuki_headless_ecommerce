<?php

namespace App\Domains\Storefront\Transformers\Product;

use App\Domains\Catalog\Models\ProductVariant;
use App\Domains\Pricing\Services\CurrencyFormatter;


final class VariantTransformer
{
    public static function transform(
        ProductVariant $variant,
        string $currency
    ): array {
        $price = $variant->priceForCurrency($currency);
        $inventory = $variant->inventory;

        $available = 0;
        if ($inventory) {
            $available = max(0, $inventory->quantity - $inventory->reserved);
        }

        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'attributes' => $variant->attributes ?? [],
            'price' => $price ? [
                'amount' => $price->amount,
                'currency' => $currency,
                'formatted' => app(CurrencyFormatter::class)
                    ->format($price->amount, $currency),
            ] : null,

            'currency' => $currency,
            'available' => $available,
            'is_active' => $variant->is_active,
            'backorder_allowed' => $variant->product->backorder_enabled,
        ];
    }
}

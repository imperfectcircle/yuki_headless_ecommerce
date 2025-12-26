<?php

namespace App\Domains\Storefront\Transformers\Product;

use App\Domains\Catalog\Models\ProductVariant;

final class VariantTransformer
{
    public static function transform(
        ProductVariant $variant,
        string $currency
    ): array {
        $price = $variant->priceForCurrency($currency);

        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'attributes' => $variant->attributes,
            'price' => $price->amount ?? 0,
            'currency' => $currency,
            'available' => ($variant->inventory?->quantity > 0) > 0
        ];
    }
}

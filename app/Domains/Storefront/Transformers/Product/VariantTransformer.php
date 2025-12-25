<?php

namespace App\Domains\Storefront\Transformers\Product;

use App\Domains\Catalog\Models\ProductVariant;
use App\Domains\Storefront\DTOs\Product\StorefrontVariantDTO;

final class VariantTransformer
{
    public function transform(
        ProductVariant $variant,
        string $currency
    ): StorefrontVariantDTO {
        $price = $variant->priceForCurrency($currency);

        return new StorefrontVariantDTO(
            id: $variant->id,
            sku: $variant->sku,
            attributes: $variant->attributes,
            price: $price->amount ?? 0,
            currency: $currency,
            available: $variant->inventory?->quantity > 0
        );
    }
}

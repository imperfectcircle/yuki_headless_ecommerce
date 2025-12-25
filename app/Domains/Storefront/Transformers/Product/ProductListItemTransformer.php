<?php

namespace App\Domains\Storefront\Transformers\Product;

use App\Domains\Catalog\Models\Product;
use App\Domains\Storefront\DTOs\Product\StorefrontProductListItemDTO;

final class ProductListItemTransformer
{
    public function transform(
        Product $product,
        string $currency,
    ): StorefrontProductListItemDTO {
        $prices = $product->variants
            ->map(fn ($variant) => $variant->priceForCurrency($currency)?->amount)
            ->filter();

            return new StorefrontProductListItemDTO(
                id: $product->id,
                slug: $product->slug,
                name: $product->name,
                priceFrom: $prices->min() ?? 0,
                currency: $currency,
            );
    }
}

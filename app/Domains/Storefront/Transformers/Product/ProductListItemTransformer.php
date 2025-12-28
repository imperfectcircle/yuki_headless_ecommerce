<?php

namespace App\Domains\Storefront\Transformers\Product;

use App\Domains\Catalog\Models\Product;

final class ProductListItemTransformer
{
    public static function transform(
        Product $product,
        string $currency,
    ): array {
        $prices = $product->variants
            ->map(fn ($variant) => $variant->priceForCurrency($currency)?->amount)
            ->filter();

            return [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'price_from' => $prices->min() ?? 0,
                'price_to' => $prices->max() ?? 0,
                'currency' => $currency,
                'has_variants' => $product->variants->count() > 1,
            ];
    }
}

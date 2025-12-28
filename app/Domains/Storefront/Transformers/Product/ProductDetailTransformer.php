<?php

namespace App\Domains\Storefront\Transformers\Product;

use App\Domains\Catalog\Models\Product;

final class ProductDetailTransformer
{
    public static function transform(
        Product $product,
        string $currency
    ): array {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'description' => $product->description ?? '',
            'backorrder_enabled' => $product->backorder_enabled,
            'variants' => $product->variants->map(
                fn ($variant) => VariantTransformer::transform($variant, $currency)
            )->toArray(),
        ];
    }
}

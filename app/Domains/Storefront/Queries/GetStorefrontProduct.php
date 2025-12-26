<?php

namespace App\Domains\Storefront\Queries;

use App\Domains\Catalog\Models\Product;
use App\Domains\Storefront\Transformers\Product\ProductDetailTransformer;

final class GetStorefrontProduct
{
    public function execute(string $slug, string $currency): array
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'variants.prices',
                'variants.inventory'
            ])
            ->firstOrFail();

        return ProductDetailTransformer::transform(
            $product,
            $currency
        );
    }
}

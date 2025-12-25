<?php

namespace App\Domains\Storefront\Queries;

use App\Domains\Catalog\Models\Product;
use App\Domains\Storefront\DTOs\Product\StorefrontProductDetailDTO;
use App\Domains\Storefront\Transformers\Product\ProductDetailTransformer;

class GetStorefrontProduct
{
    public function __construct(
        protected ProductDetailTransformer $transformer
    ) {}
    public function execute(string $slug, string $currency): StorefrontProductDetailDTO
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'variants.prices',
                'variants.inventory'
            ])
            ->firstOrFail();

        return $this->transformer->transform(
            $product,
            $currency
        );
    }
}

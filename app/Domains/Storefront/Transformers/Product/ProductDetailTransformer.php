<?php

namespace App\Domains\Storefront\Transformers\Product;

use App\Domains\Catalog\Models\Product;
use App\Domains\Storefront\DTOs\Product\StorefrontProductDetailDTO;

final class ProductDetailTransformer
{
    public function __construct(
        protected VariantTransformer $variantTransformer
    ) {}

    public function transform(
        Product $product,
        string $currency
    ): StorefrontProductDetailDTO {
        $variants = $product->variants->map(
            fn ($variant) => $this->variantTransformer->transform(
                $variant,
                $currency
            )
        )->all();

        return new StorefrontProductDetailDTO(
            id: $product->id,
            slug: $product->slug,
            name: $product->name,
            description: $product->description ?? '',
            variants: $variants
        );
    }
}

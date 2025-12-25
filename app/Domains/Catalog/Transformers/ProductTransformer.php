<?php

namespace App\Domains\Catalog\Transformers;

use App\Domains\Catalog\DTOs\ProductDTO;
use App\Domains\Catalog\Models\Product;
use Illuminate\Support\Str;

class ProductTransformer
{
    public static function fromModel(Product $product, bool $withVariants = false): ProductDTO
    {
        $seo = [
            'title' => $product->name,
            'description' => $product->description
                ? Str::limit($product->description, 160)
                : null
        ];

        return new ProductDTO(
            id: $product->id,
            name: $product->name,
            slug: $product->slug,
            description: $product->description,
            variants: $withVariants
                ? $product->variants
                    ->map(fn ($variant) =>
                        ProductVariantTransformer::fromModel($variant)
                    )
                    ->all()
                : [],
            seo: $seo,
        );
    }
}

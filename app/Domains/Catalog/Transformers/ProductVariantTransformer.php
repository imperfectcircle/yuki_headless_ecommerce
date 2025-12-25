<?php

namespace App\Domains\Catalog\Transformers;

use App\Domains\Catalog\DTOs\ProductVariantDTO;
use App\Domains\Catalog\Models\ProductVariant;

class ProductVariantTransformer
{
    public static function fromModel(ProductVariant $variant): ProductVariantDTO
    {
        return new ProductVariantDTO(
            id: $variant->id,
            sku: $variant->sku,
            attributes: $variant->attributes ?? [],
        );
    }
}

<?php

namespace App\Domains\Storefront\Transformers;

use App\Domains\Catalog\Models\Product;

class ProductTransformer
{
    public static function transform(Product $product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'description' => $product->description,
            'status' => $product->status,
        ];
    }
}

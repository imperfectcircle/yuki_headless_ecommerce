<?php

namespace App\Domains\Storefront\Transformers\Cart;

use App\Domains\Cart\Models\CartItem;
use App\Domains\Storefront\DTOs\Cart\StorefrontCartItemDTO;

final class CartItemTransformer
{
    public function transform(CartItem $item): StorefrontCartItemDTO
    {
        return new StorefrontCartItemDTO(
            variantId: $item->product_variant_id,
            name: $item->productVariant->product->name,
            attributes: $item->productVariant->attributes,
            unitPrice: $item->unit_price,
            quantity: $item->quantity,
            total: $item->unit_price * $item->quantity
        );
    }
}

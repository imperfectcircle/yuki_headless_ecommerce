<?php

namespace App\Domains\Storefront\Transformers\Cart;

use App\Domains\Cart\Models\Cart;
use App\Domains\Storefront\DTOs\Cart\StorefrontCartDTO;

final class CartTransformer
{
    public function __construct(
        protected CartItemTransformer $itemTransformer
    ) {}

    public function transform(Cart $cart): StorefrontCartDTO
    {
        $items = $cart->items->map(
            fn ($item) => $this->itemTransformer->transform($item)
        )->all();

        return new StorefrontCartDTO(
            token: $cart->token,
            currency: $cart->currency,
            items: $items,
            subtotal: $cart->items->sum(
                fn ($item) => $item->unit_price * $item->quantity
            )
        );
    }
}

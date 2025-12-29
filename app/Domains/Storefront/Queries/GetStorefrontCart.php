<?php

namespace App\Domains\Storefront\Queries;

use App\Domains\Cart\Models\Cart;
use App\Domains\Storefront\DTOs\Cart\StorefrontCartDTO;
use App\Domains\Storefront\Transformers\Cart\CartTransformer;

class GetStorefrontCart
{
    public function __construct(
        protected CartTransformer $transformer
    ) {}

    public function execute(string $token): StorefrontCartDTO
    {
        $cart = Cart::query()
            ->where('token', $token)
            ->with(['items.productVariant.product'])
            ->firstOrFail();

        return $this->transformer->transform($cart);
    }
}

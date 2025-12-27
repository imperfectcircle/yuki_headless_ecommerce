<?php

namespace App\Domains\Storefront\DTOs\Cart;

final readonly class AddToCartRequestDTO
{
    public function __construct(
        public int $variantId,
        public int $quantity
    ) {}
}

<?php

namespace App\Domains\Storefront\DTOs\Cart;

final readonly class StorefrontCartTotalsDTO
{
    public function __construct(
        public int $subtotal,
        public int $tax,
        public int $grandTotal,
    ) {}

    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'grand_total' => $this->grandTotal,
        ];
    }
}

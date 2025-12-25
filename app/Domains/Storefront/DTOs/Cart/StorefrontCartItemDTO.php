<?php

namespace App\Domains\Storefront\DTOs\Cart;

final readonly class StorefrontCartItemDTO
{
    public function __construct(
        public int $variantId,
        public string $name,
        public array $attributes,
        public int $unitPrice,
        public int $quantity,
        public int $total,
    ) {}

    public function toArray(): array
    {
        return [
            'variant_id' => $this->variantId,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'total' => $this->total,
        ];
    }
}

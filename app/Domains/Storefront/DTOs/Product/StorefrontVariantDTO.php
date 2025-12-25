<?php

namespace App\Domains\Storefront\DTOs\Product;

final readonly class StorefrontVariantDTO
{
    public function __construct(
        public int $id,
        public string $sku,
        public array $attributes,
        public int $price,
        public string $currency,
        public bool $available
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'attributes' => $this->attributes,
            'price' => $this->price,
            'currency' => $this->currency,
            'available' => $this->available,
        ];
    }
}

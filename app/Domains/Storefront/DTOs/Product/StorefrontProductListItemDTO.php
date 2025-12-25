<?php

namespace App\Domains\Storefront\DTOs\Product;

final readonly class StorefrontProductListItemDTO
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public int $priceFrom,
        public string $currency,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'price_from' => $this->priceFrom,
            'currency' => $this->currency,
        ];
    }
}

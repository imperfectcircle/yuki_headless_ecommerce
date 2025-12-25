<?php

namespace App\Domains\Storefront\DTOs\Product;

final readonly class StorefrontProductDetailDTO
{
    /**
    * @param StorefrontVariantDTO[] $variants
    */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string $description,
        public array $variants
    ) {}

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'slug'        => $this->slug,
            'name'        => $this->name,
            'description' => $this->description,
            'variants'    => array_map(
                fn (StorefrontVariantDTO $variant) => $variant->toArray(),
                $this->variants
            ),
        ];
    }
}

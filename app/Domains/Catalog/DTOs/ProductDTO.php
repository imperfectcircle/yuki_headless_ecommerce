<?php

namespace App\Domains\Catalog\DTOs;

class ProductDTO
{
    /**
     * @param ProductVariantDTO[] $variants
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public array $variants = [],
        public array $seo = [],
    ) {}
}

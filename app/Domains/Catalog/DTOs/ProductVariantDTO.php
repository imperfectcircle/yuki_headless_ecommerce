<?php

namespace App\Domains\Catalog\DTOs;

class ProductVariantDTO
{
    public function __construct(
        public int $id,
        public string $sku,
        public array $attributes, // es: ['color' => 'black', 'size' => 'M']
    ) {}
}

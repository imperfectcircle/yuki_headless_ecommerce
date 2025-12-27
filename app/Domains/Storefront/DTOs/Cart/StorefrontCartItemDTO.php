<?php

namespace App\Domains\Storefront\DTOs\Cart;

final readonly class StorefrontCartItemDTO
{
    public function __construct(
        public int $id,
        public int $productId,
        public int $variantId,
        public string $sku,
        public string $name,
        public int $unitPrice,
        public int $quantity,
        public int $total,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'sku' => $this->sku,
            'name' => $this->name,
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'total' => $this->total,
        ];
    }
}

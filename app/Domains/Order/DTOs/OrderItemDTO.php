<?php

namespace App\Domains\Order\DTOs;

final readonly class OrderItemDTO
{
    public function __construct(
        public int $id,
        public int $productVariantId,
        public string $sku,
        public string $name,
        public ?array $attributes,
        public int $unitPrice,
        public int $quantity,
        public int $total,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->productVariantId,
            'sku' => $this->sku,
            'name' => $this->name,
            'attributes' => $this->attributes ?? [],
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'total' => $this->total,
        ];
    }
}

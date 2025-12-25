<?php

namespace App\Domains\Storefront\DTOs\Order;

final readonly class StorefrontOrderDTO
{
    public function __construct(
        public int $id,
        public string $status,
        public int $subtotal,
        public int $taxTotal,
        public int $shippingTotal,
        public int $grandTotal,
        public string $currency
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->taxTotal,
            'shipping_total' => $this->shippingTotal,
            'grand_total' => $this->grandTotal,
            'currency' => $this->currency,
        ];
    }
}

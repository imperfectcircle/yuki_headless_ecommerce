<?php

namespace App\Domains\Order\DTOs;

final readonly class OrderListItemDTO
{
    public function __construct(
        public int $id,
        public string $number,
        public string $status,
        public string $customerEmail,
        public ?string $customerFullName,
        public int $grandTotal,
        public string $currency,
        public int $itemsCount,
        public string $createdAt,
        public ?string $reservedUntil = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'customer' => [
                'email' => $this->customerEmail,
                'full_name' => $this->customerFullName,
            ],
            'total' => [
                'amount' => $this->grandTotal,
                'currency' => $this->currency,
                'formatted' => $this->formatAmount()
            ],
            'items_count' => $this->itemsCount,
            'created_at' => $this->createdAt,
            'reserved_until' => $this->reservedUntil,
        ];
    }

    private function formatAmount(): string
    {
        return number_format($this->grandTotal / 100, 2) . ' ' . $this->currency;
    }
}

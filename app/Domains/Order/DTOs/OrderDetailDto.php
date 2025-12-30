<?php

namespace App\Domains\Order\Dtos;

final readonly class OrderDetailDto
{
    public function __construct(
        public int $id,
        public string $number,
        public string $status,
        public string $currency,
        public int $subtotal,
        public int $taxTotal,
        public int $shippingTotal,
        public int $grandTotal,
        public string $customerEmail,
        public ?string $customerFullName,
        public ?string $customerPhone,
        public ?array $shippingAddress,
        public ?array $billingAddress,
        public bool $guestCheckout,
        public ?int $customerProfileId,
        public array $items,
        public array $timeline,
        public ?string $reservedUntil,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'currency' => $this->currency,
            'totals' => [
                'subtotal' => $this->subtotal,
                'tax_total' => $this->taxTotal,
                'shipping_total' => $this->shippingTotal,
                'grand_total' => $this->grandTotal,
                'formatted' => [
                    'subtotal' => $this->format($this->subtotal),
                    'tax_total' => $this->format($this->taxTotal),
                    'shipping_total' => $this->format($this->shippingTotal),
                    'grand_total' => $this->format($this->grandTotal),
                ],
            ],
            'customer' => [
                'email' => $this->customerEmail,
                'full_name' => $this->customerFullName,
                'phone' => $this->customerPhone,
                'profile_id' => $this->customerProfileId,
                'is_guest' => $this->guestCheckout,
            ],
            'addressed' => [
                'shipping' => $this->shippingAddress,
                'billing' => $this->billingAddress,
            ],
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
            'timeline' => array_map(fn($event) => $event->toArray(), $this->timeline),
            'reserved_until' => $this->reservedUntil,
            'dates' => [
                'created_at' => $this->createdAt,
                'updated_at' => $this->updatedAt,
            ],
        ];
    }

    private function format(int $amount): string
    {
        return number_format($amount / 100, 2) . ' ' . $this->currency;
    }
}

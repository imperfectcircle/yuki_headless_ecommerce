<?php

namespace App\Domains\Order\DTOs;

final readonly class OrderFiltersDTO
{
    public function __construct(
        public ?string $status = null,
        public ?string $customerEmail = null,
        public ?string $orderNumber = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?bool $guestOnly = null,
        public int $perPage = 15,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            status: $data['status'] ?? null,
            customerEmail: $data['customer_email'] ?? null,
            orderNumber: $data['order_number'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            guestOnly: isset($data['guest_only']) ? (bool) $data['guest_only'] : null,
            perPage: (int) ($data['per_page'] ?? 15),
            sortBy: $data['sort_by'] ?? 'created_at',
            sortDirection: $data['sort_direction'] ?? 'desc'
        );
    }
}

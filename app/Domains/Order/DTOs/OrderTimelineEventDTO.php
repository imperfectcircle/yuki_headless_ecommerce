<?php

namespace App\Domains\Order\Dtos;

final readonly class OrderTimelineEventDTO
{
    public function __construct(
        public string $type,
        public string $status,
        public string $message,
        public ?string $userName,
        public ?array $metadata,
        public string $cratedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'status' => $this->status,
            'message' => $this->message,
            'user' => $this->userName,
            'metadata' => $this->metadata,
            'created_at' => $this->cratedAt,
        ];
    }
}

<?php

namespace App\Domains\Payments\DTOs;

class PaymentWebhookData
{
    public const EVENT_PAID = 'paid';
    public const EVENT_FAILED = 'failed';
    public const EVENT_UNKNOWN = 'unknown';

    public function __construct(
        public string $provider,
        public string $providerReference,
        public string $eventType,
        public array $rawPayload = [],
    ) {}
}

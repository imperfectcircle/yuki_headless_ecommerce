<?php

namespace App\Domains\Payments\DTOs;

class PaymentIntentData
{
    public function __construct(
        public string $provider,
        public string $providerReference,
        public string $redirectUrl,
        public array $rawPayload = []
    ) {}
}

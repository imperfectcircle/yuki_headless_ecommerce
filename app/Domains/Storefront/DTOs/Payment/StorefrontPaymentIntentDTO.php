<?php

namespace App\Domains\Storefront\DTOs\Payment;

final readonly class StorefrontPaymentIntentDTO
{
    public function __construct(
        public string $provider,
        public string $redirectUrl,
    ) {}

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'redirect_url' => $this->redirectUrl,
        ];
    }
}

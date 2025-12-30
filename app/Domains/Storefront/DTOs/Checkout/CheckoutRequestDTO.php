<?php

namespace App\Domains\Storefront\DTOs\Checkout;

final readonly class CheckoutRequestDTO
{
    public function __construct(
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $phone,
        public ShippingAddressDTO $shippingAddress,
        public BillingAddressDTO $billingAddress,
        public ?string $paymentProvider = null,
        public ?int $customerProfileId = null,
    ) {}

    public function getFullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    public function isGuest(): bool
    {
        return is_null($this->customerProfileId);
    }
}

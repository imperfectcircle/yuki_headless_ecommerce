<?php

namespace App\Domains\Storefront\DTOs\Checkout;

final readonly class BillingAddressDTO
{
    public function __construct(
        public string $name,
        public ?string $company,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $city,
        public ?string $state,
        public string $postalCode,
        public string $country,
        public ?string $phone,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'company' => $this->company,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'phone' => $this->phone,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            company: $data['company'] ?? null,
            addressLine1: $data['address_line_1'],
            addressLine2: $data['address_line_2'] ?? null,
            city: $data['city'],
            state: $data['state'] ?? null,
            postalCode: $data['postal_code'],
            country: $data['country'],
            phone: $data['phone'] ?? null,
        );
    }
}

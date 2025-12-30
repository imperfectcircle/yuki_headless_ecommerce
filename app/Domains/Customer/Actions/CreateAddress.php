<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Models\Address;
use App\Domains\Customer\Models\CustomerProfile;

class CreateAddress
{
    public function execute(
        CustomerProfile $profile,
        string $type,
        string $name,
        string $addressLine1,
        string $city,
        string $postalCode,
        string $country,
        ?string $company = null,
        ?string $addressLine2 = null,
        ?string $state = null,
        ?string $phone = null,
        bool $isDefault = false
    ): Address {
        $address = $profile->addresses()->create([
            'type' => $type,
            'name' => $name,
            'company' => $company,
            'address_line_1' => $addressLine1,
            'address_line_2' => $addressLine2,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postalCode,
            'country' => $country,
            'phone' => $phone,
            'is_default' => false,
        ]);

        if ($isDefault) {
            $address->setAsDefault();
        }

        return $address;
    }
}

<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Customer\Models\StorefrontUser;

class CreateCustomerProfile
{
    public function execute(
        ?StorefrontUser $user,
        string $email,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
        bool $acceptsMarketing = false,
    ): CustomerProfile {
        return CustomerProfile::create([
            'storefront_user_id' => $user?->id,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'accepts_marketing' => $acceptsMarketing,
        ]);
    }
}

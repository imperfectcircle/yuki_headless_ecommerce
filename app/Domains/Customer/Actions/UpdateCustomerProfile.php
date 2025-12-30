<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Models\CustomerProfile;

class UpdateCustomerProfile
{
    public function execute(CustomerProfile $profile, array $data): CustomerProfile
    {
        $profile->update($data);
        return $profile;
    }
}

<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Models\Address;

class UpdateAddress
{
    public function execute(Address $address, array $data): Address
    {
        $address->update($data);

        if (isset($data['is_default']) && $data['is_default']) {
            $address->setAsDefault();
        }

        return $address;
    }
}

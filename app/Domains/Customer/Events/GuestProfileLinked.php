<?php

namespace App\Domains\Customer\Events;

use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuestProfileLinked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StorefrontUser $user,
        public CustomerProfile $profile,
        public int $ordersLinked
    ) {}
}

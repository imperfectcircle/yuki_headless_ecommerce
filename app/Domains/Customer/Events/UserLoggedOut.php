<?php

namespace App\Domains\Customer\Events;

use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user logs out
 */
class UserLoggedOut
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StorefrontUser $user,
    ) {}
}

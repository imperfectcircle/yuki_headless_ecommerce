<?php

namespace App\Domains\Customer\Events;

use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// TODO
/**
 * Dispatched when a user successfully resets their password
 * 
 * This event is part of the password reset feature (not yet implemented)
 */
class PasswordResetCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StorefrontUser $user,
        public string $ipAddress,
    ) {}
}

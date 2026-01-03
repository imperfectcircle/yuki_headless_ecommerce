<?php

namespace App\Domains\Customer\Events;

use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// TODO
/**
 * Dispatched when a user requests a password reset
 * 
 * This event is part of the password reset feature (not yet implemented)
 * It will be dispatched when:
 * - User clicks "Forgot Password"
 * - System generates reset token
 * - Reset email is about to be sent
 */
class PasswordResetRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StorefrontUser $user,
        public string $resetToken,
        public string $ipAddress,
    ) {}
}

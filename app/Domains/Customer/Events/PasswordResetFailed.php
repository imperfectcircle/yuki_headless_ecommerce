<?php

namespace App\Domains\Customer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
// TODO
/**
 * Dispatched when a password reset attempt fails
 * 
 * This event is part of the password reset feature (not yet implemented)
 * Can be used for security monitoring and fraud detection
 */
class PasswordResetFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $email,
        public string $reason,
        public string $ipAddress,
    ) {}
}

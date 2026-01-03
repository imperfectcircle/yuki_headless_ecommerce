<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Events\EmailVerified;
use App\Domains\Customer\Models\StorefrontUser;
use DomainException;

class VerifyStorefrontUserEmail
{
    /**
     * Verify user's email using verification token
     * 
     * @param string $token
     * @return StorefrontUser
     * @throws DomainException
     */
    public function execute(string $token): StorefrontUser
    {
        $user = StorefrontUser::where('verification_token', $token)->first();

        if (!$user) {
            throw new DomainException('Invalid or expired verification token.');
        }

        if ($user->hasVerifiedEmail()) {
            throw new DomainException('Email already verified.');
        }

        $user->markEmailAsVerified();

        EmailVerified::dispatch($user);

        return $user;
    }
}
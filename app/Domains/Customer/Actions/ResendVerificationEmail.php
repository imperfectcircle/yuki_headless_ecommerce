<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Events\VerificationEmailSent;
use App\Domains\Customer\Models\StorefrontUser;
use App\Mail\Auth\VerifyEmailMail;
use DomainException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResendVerificationEmail
{
    public function execute(StorefrontUser $user): StorefrontUser
    {
        if ($user->hasVerifiedEmail()) {
            throw new DomainException('Email already verified.');
        }

        // Regenerate token for security
        $user->update([
            'verification_token' => Str::random(64),
        ]);

        Mail::to($user->email)->send(new VerifyEmailMail($user));

        VerificationEmailSent::dispatch($user, true);

        return $user->fresh();
    }
}

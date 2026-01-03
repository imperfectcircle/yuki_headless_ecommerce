<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Events\VerificationEmailSent;
use App\Domains\Customer\Models\StorefrontUser;
use App\Mail\Auth\VerifyEmailMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterStorefrontUser
{
    /**
     * Register a new storefront user
     * 
     * @param string $email
     * @param string $password
     * @param bool $sendVerificationEmail Whether to send verification email
     * @return StorefrontUser
     */
    public function execute(
        string $email,
        string $password,
        bool $sendVerificationEmail = true,
    ): StorefrontUser
    {
        $user = StorefrontUser::create([
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified' => false,
            'verification_token' => Str::random(64),
        ]);

        if ($sendVerificationEmail) {
            Mail::to($user->email)->send(new VerifyEmailMail($user));
            VerificationEmailSent::dispatch($user, false);
        }

        // Note: UserRegistered event is dispatched in the controller
        // after guest profile linking, so it includes complete info

        return $user;
    }
}

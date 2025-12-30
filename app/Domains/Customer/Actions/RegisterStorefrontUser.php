<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterStorefrontUser
{
    public function execute(string $email, string $password): StorefrontUser
    {
        return StorefrontUser::create([
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified' => false,
            'verification_token' => Str::random(64),
        ]);
    }
}

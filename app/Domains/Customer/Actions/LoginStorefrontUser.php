<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginStorefrontUser
{
    public function execute(string $email, string $password): StorefrontUser
    {
        $user = StorefrontUser::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user;
    }
}

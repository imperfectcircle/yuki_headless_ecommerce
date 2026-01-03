<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPassword
{
    public function execute(string $email, string $token, string $password): StorefrontUser
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record || !hash_equals($record->token, hash('sha256', $token))) {
            throw ValidationException::withMessages([
                'email' => ['Invalid or expired password reset token.'],
            ]);
        }

        // Check if token is expired (24 hours)
        if (now()->diffInHours($record->created_at) > 24) {
            throw ValidationException::withMessages([
                'email' => ['Password reset token has expired.'],
            ]);
        }

        $user = StorefrontUser::where('email', $email)->firstOrFail();

        $user->update([
            'password' => Hash::make($password),
        ]);

        // Delete token after use
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return $user->fresh();
    }
}


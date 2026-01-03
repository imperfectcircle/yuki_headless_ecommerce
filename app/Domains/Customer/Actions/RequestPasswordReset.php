<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Events\PasswordResetRequested;
use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// TODO
class RequestPasswordReset
{
    public function execute(string $email): void
    {
        $user = StorefrontUser::where('email', $email)->first();

        if (!$user) {
            // Don't reveal if email exists (security)
            return;
        }

        $token = Str::random(64);

        // Store in password_resets table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'email' => $email,
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ]
        );

        PasswordResetRequested::dispatch($user, $token);
    }
}

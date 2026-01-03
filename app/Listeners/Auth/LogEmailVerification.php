<?php

namespace App\Listeners\Auth;

use App\Domains\Customer\Events\EmailVerified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogEmailVerification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(EmailVerified $event): void
    {
        Log::info('User email verified', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'verified_at' => $event->user->email_verified_at?->toDateTimeString(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}

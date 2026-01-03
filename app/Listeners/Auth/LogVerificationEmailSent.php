<?php

namespace App\Listeners\Auth;

use App\Domains\Customer\Events\VerificationEmailSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogVerificationEmailSent implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(VerificationEmailSent $event): void
    {
        $action = $event->isResend ? 'Resent' : 'Sent';

        Log::info("{$action} verification email", [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'is_resend' => $event->isResend,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}

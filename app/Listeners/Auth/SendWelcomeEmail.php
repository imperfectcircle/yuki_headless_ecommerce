<?php

namespace App\Listeners\Auth;

use App\Domains\Customer\Events\EmailVerified;
use App\Mail\Auth\WelcomeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(EmailVerified $event): void
    {
        // Send welcome email after email verification
        Mail::to($event->user->email)->send(new WelcomeMail($event->user));
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(EmailVerified $event): bool
    {
        return true;
    }
}

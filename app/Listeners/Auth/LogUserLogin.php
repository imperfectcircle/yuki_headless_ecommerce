<?php

namespace App\Listeners\Auth;

use App\Domains\Customer\Events\UserLoggedIn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogUserLogin implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UserLoggedIn $event): void
    {
        Log::info('User logged in', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}

<?php

namespace App\Listeners\Auth;

use App\Domains\Customer\Events\GuestProfileLinked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdminOfProfileLinking implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(GuestProfileLinked $event): void
    {
        // Log the conversion from guest to registered
        Log::info('Guest profile linked to registered user', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'profile_id' => $event->profile->id,
            'orders_linked' => $event->ordersLinked,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Here you could also:
        // - Send notification to admin
        // - Update analytics
        // - Trigger marketing automation
        // - Send personalized email to user about their order history
    }
}
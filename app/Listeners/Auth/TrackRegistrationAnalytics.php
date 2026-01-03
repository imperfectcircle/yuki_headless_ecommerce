<?php

namespace App\Listeners\Auth;

use App\Domains\Customer\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TrackRegistrationAnalytics implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        // Track registration in analytics
        Log::info('New user registered', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'has_linked_profile' => $event->hasLinkedProfile,
            'linked_orders_count' => $event->linkedOrdersCount,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Here you could integrate with:
        // - Google Analytics
        // - Mixpanel
        // - Segment
        // - Custom analytics system
        
        // Example for Google Analytics 4:
        // $this->trackToGA4($event);
        
        // Example for Mixpanel:
        // $this->trackToMixpanel($event);
    }

    /**
     * Example method for GA4 integration
     */
    protected function trackToGA4(UserRegistered $event): void
    {
        // This is a placeholder - implement your actual GA4 tracking
        // You might use a package like spatie/laravel-analytics or custom implementation
        
        // Example structure:
        // Analytics::track('user_registered', [
        //     'user_id' => $event->user->id,
        //     'email' => $event->user->email,
        //     'has_previous_orders' => $event->hasLinkedProfile,
        //     'previous_orders_count' => $event->linkedOrdersCount,
        // ]);
    }

    /**
     * Example method for Mixpanel integration
     */
    protected function trackToMixpanel(UserRegistered $event): void
    {
        // Example structure:
        // Mixpanel::track('User Registered', [
        //     'distinct_id' => $event->user->id,
        //     'email' => $event->user->email,
        //     'has_linked_profile' => $event->hasLinkedProfile,
        //     'linked_orders_count' => $event->linkedOrdersCount,
        //     'registration_date' => now()->toDateTimeString(),
        // ]);
        //
        // Mixpanel::people()->set($event->user->id, [
        //     '$email' => $event->user->email,
        //     '$created' => now()->toDateTimeString(),
        //     'registered_user' => true,
        // ]);
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(UserRegistered $event): bool
    {
        return true;
    }
}
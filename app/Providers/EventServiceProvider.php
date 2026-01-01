<?php

namespace App\Providers;

use App\Domains\Customer\Events\EmailVerified;
use App\Listeners\Auth\NotifyAdminOfProfileLinking;
use App\Domains\Customer\Events\GuestProfileLinked;
use App\Domains\Customer\Events\VerificationEmailSent;
use App\Listeners\Auth\SendWelcomeEmail;
use App\Listeners\Auth\LogEmailVerification;
use App\Domains\Customer\Events\UserLoggedIn;
use App\Domains\Customer\Events\UserLoggedOut;
use App\Domains\Customer\Events\UserRegistered;
use App\Domains\Order\Events\OrderCancelled;
use App\Domains\Order\Events\OrderCreated;
use App\Domains\Order\Events\OrderDelivered;
use App\Domains\Order\Events\OrderFulfilled;
use App\Domains\Order\Events\OrderPaid;
use App\Domains\Order\Events\OrderProcessingStarted;
use App\Domains\Order\Events\OrderRefunded;
use App\Domains\Order\Events\OrderReservationExpired;
use App\Domains\Order\Events\OrderReserved;
use App\Domains\Order\Events\OrderShipped;
use App\Domains\Order\Events\OrderStatusChanged;
use App\Domains\Payments\Events\PaymentCreated;
use App\Domains\Payments\Events\PaymentFailed;
use App\Domains\Payments\Events\PaymentSuccessful;
use App\Listeners\Auth\LogUserLogin;
use App\Listeners\Auth\LogVerificationEmailSent;
use App\Listeners\Order\SendOrderConfirmationEmail;
use App\Listeners\Order\SendOrderShippedEmail;
use App\Listeners\Order\NotifyAdminOfNewOrder;
use App\Listeners\Order\SendOrderCancelledEmail;
use App\Listeners\Auth\TrackRegistrationAnalytics;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ====================================================================
        // Authentication Events
        // ====================================================================
        UserRegistered::class => [
            TrackRegistrationAnalytics::class,
            // Add more listeners:
            // - SendRegistrationNotificationToAdmin::class
            // - CreateCustomerProfileForUser::class
            // - SyncToMarketingPlatform::class
        ],

        UserLoggedIn::class => [
            LogUserLogin::class,
            // Add more listeners:
            // - UpdateLastLoginTimestamp::class
            // - DetectSuspiciousActivity::class
            // - TrackLoginAnalytics::class
        ],

        UserLoggedOut::class => [
            // Add listeners here if needed
            // - LogUserLogout::class
            // - CleanupUserSessions::class
        ],

        EmailVerified::class => [
            LogEmailVerification::class,
            SendWelcomeEmail::class,
            // Add more listeners:
            // - GrantVerifiedUserBenefits::class
            // - SendWelcomeDiscount::class
            // - UpdateMarketingStatus::class
        ],

        VerificationEmailSent::class => [
            LogVerificationEmailSent::class,
            // Add more listeners:
            // - TrackEmailDelivery::class
            // - UpdateEmailSentMetrics::class
        ],

        GuestProfileLinked::class => [
            NotifyAdminOfProfileLinking::class,
            // Add more listeners:
            // - SendOrderHistoryEmail::class
            // - UpdateCustomerLifetimeValue::class
            // - TriggerMarketingAutomation::class
        ],
        
        // ====================================================================
        // Order Events
        // ====================================================================
        OrderCreated::class => [
            NotifyAdminOfNewOrder::class,
        ],

        OrderReserved::class => [
            // Add listeners here if needed
            // e.g., SendReservationConfirmation::class
        ],

        OrderPaid::class => [
            SendOrderConfirmationEmail::class,
            // Add more listeners:
            // - UpdateCustomerStatistics::class
            // - NotifyWarehouseOfNewOrder::class
            // - TrackConversionInAnalytics::class
        ],

        OrderProcessingStarted::class => [
            // Add listeners here if needed
        ],

        OrderFulfilled::class => [
            // Add listeners here if needed
        ],

        OrderShipped::class => [
            SendOrderShippedEmail::class, 
        ],

        OrderDelivered::class => [
            // Add listeners here if needed
        ],

        OrderCancelled::class => [
            SendOrderCancelledEmail::class,
            // Add listeners here if needed
            // e.g., NotifyCustomerOfCancellation::class
        ],

        OrderReservationExpired::class => [
            // Add listeners here if needed
            // e.g., SendAbandonedCartReminder::class
        ],

        OrderRefunded::class => [
            // Add listeners here if needed
        ],

        OrderStatusChanged::class => [
            // Generic listener for all status changes
        ],


        // ====================================================================
        // Payment Events
        // ====================================================================
        PaymentCreated::class => [],
        PaymentSuccessful::class => [],
        PaymentFailed::class => [],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

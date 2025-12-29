<?php

namespace App\Domains\Payments\Events;

use App\Domains\Payments\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base event for all payment-related events
 */
abstract class PaymentEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payment $payment
    ) {}
}

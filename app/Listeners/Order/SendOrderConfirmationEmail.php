<?php

namespace App\Listeners\Order;

use App\Domains\Order\Events\OrderPaid;
use App\Mail\Order\OrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    /**
    * The number of times the job may be attempted
    */
    public int $tries = 3;

    /**
    * The number of seconds to wait before retrying
    */
    public int $backoff = 60;

    /**
    * Handle the event.
    */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        if (!$order->customer_email) {
            Log::warning('Cannot send order confirmation: no customer email', [
                'order_id' => $order->id,
            ]);
            return;
        }

        try {
            Mail::to($order->customer_email)
                ->send(new OrderConfirmationMail($order));

            Log::info('Order confirmation email sent', [
                'order_id' => $order->id,
                'email' => $order->customer_email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation email', [
                'order_id' => $order->id,
                'email' => $order->customer_email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
    * Determine if the listener should be queued.
    */
    public function shouldQueue(OrderPaid $event): bool
    {
        return true;
    }
}

<?php

namespace App\Listeners\Order;

use App\Domains\Order\Events\OrderShipped;
use App\Mail\Order\OrderShippedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderShippedEmail implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;

    public function handle(OrderShipped $event): void
    {
        $order = $event->order;

        if (!$order->customer_email) {
            Log::warning('Cannot send shipping notification: no customer email', [
                'order_id' => $order->id,
            ]);
            return;
        }

        try {
            Mail::to($order->customer_email)
                ->send(new OrderShippedMail($order, $event->trackingNumber, $event->carrier));

            Log::info('Order shipped email sent', [
                'order_id' => $order->id,
                'email' => $order->customer_email,
                'tracking_number' => $event->trackingNumber,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order shipped email', [
                'order_id' => $order->id,
                'email' => $order->customer_email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function shouldQueue(OrderShipped $event): bool
    {
        return true;
    }
}

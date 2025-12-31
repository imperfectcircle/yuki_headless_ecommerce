<?php

namespace App\Listeners\Order;

use App\Domains\Order\Events\OrderCancelled;
use App\Mail\Order\OrderCancelledMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderCancelledEmail implements ShouldQueue
{
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;

        if (!$order->customer_email) {
            Log::warning('Cannot send cancel notification: no customer email', [
                'order_id' => $order->id,
            ]);
            return;
        }

        try {
            Mail::to($order->customer_email)
                ->send(new OrderCancelledMail($order));

            Log::info('Order cancelled email sent', [
                'order_id' => $order->id,
                'email' => $order->customer_email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order cancelled email', [
                'order_id' => $order->id,
                'email' => $order->customer_email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function shouldQueue(OrderCancelled $event): bool
    {
        return true;
    }
}

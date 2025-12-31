<?php

namespace App\Listeners\Order;

use App\Domains\Order\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdminOfNewOrder implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        Log::info('New order created', [
            'order_id' => $order->id,
            'order_number' => $order->number,
            'customer_email' => $order->customer_email,
            'total' => $order->grand_total,    
        ]);

        // TODO
        // You can send notification to Slack, email, or other channels
        // Example: Notification::route('slack', config('services.slack.webhook'))
        //     ->notify(new NewOrderNotification($order));
    }
}

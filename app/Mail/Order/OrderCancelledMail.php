<?php

namespace App\Mail\Order;

use App\Domains\Order\Models\Order;
use Illuminate\Mail\Mailable;

class OrderCancelledMail extends Mailable
{
    public function __construct(
        public Order $order,
    ) {}

    public function build():self
    {
        return $this
            ->subject(__('mail.order_cancelled_subject', [
                'number' => $this->order->number,
            ]))
            ->markdown('emails.order.cancelled', [
                'order' => $this->order,
            ]);
    }
}

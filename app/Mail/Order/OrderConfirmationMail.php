<?php

namespace App\Mail\Order;

use App\Domains\Order\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function build(): self
    {
        return $this
            ->subject(__('mail.order_confirmation_subject', [
                'id' => $this->order->id,
            ]))
            ->markdown('emails.orders-confirmation', [
                'order' => $this->order,
            ]);
    }
}

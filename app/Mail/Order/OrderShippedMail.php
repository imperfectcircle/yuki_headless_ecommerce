<?php

namespace App\Mail\Order;

use App\Domains\Order\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $trackingNumber = null,
        public ?string $carrier = null,
    ) {}

    public function build():self
    {
        return $this
            ->subject(__('mail.order_shipped_subject', [
                'number' => $this->order->number,
            ]))
            ->markdown('emails.order.shipped', [
                'order' => $this->order,
                'trackingNumber' => $this->trackingNumber,
                'carrier' => $this->carrier,
            ]);
    }
}

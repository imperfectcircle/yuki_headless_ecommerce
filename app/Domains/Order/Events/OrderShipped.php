<?php

namespace App\Domains\Order\Events;

use App\Domains\Order\Models\Order;

class OrderShipped extends OrderEvent
{
    public function __construct(
        public Order $order,
        public ?string $trackingNumber = null,
        public ?string $carrier,
    ) {}
}

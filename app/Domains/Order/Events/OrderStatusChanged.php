<?php

namespace App\Domains\Order\Events;

use App\Domains\Order\Models\Order;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $newStatus,
        public ?string $note = null,
    ) {}
}

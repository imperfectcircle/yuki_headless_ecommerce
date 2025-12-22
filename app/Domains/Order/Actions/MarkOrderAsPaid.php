<?php

namespace App\Domains\Order\Actions;

use App\Domains\Order\Models\Order;
use DomainException;

class MarkOrderAsPaid
{
    public function execute(Order $order): void
    {
        if (!$order->canBePaid()) {
            throw new DomainException(
                'Order cannot be marked as paid.'
            );
        }

        $order->markAsPaid();
    }
}

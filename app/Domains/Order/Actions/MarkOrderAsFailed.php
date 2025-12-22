<?php

namespace App\Domains\Order\Actions;

use App\Domains\Order\Models\Order;
use DomainException;

class MarkOrderAsFailed
{
    public function execute(Order $order): void
    {
        if (!$order->canBeFailed()) {
            throw new DomainException(
                'Order cannot be marked as failed.'
            );
        }

        $order->markAsFailed();
    }
}

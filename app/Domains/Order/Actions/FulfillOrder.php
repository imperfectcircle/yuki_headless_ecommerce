<?php

namespace App\Domains\Order\Actions;

use App\Domains\Order\Events\OrderFulfilled;
use App\Domains\Order\Models\Order;
use DomainException;

class FulfillOrder
{
    public function execute(Order $order, ?int $userId = null): Order
    {
        if (!$order->isProcessing()) {
            throw new DomainException('Only processing orders can be fulfilled.');
        }

        $order->markAsFulfilled('Order fulfilled and ready to ship', $userId);

        OrderFulfilled::dispatch($order);

        return $order->fresh();
    }
}

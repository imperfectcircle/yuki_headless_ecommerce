<?php

namespace App\Domains\Order\Actions;

use App\Domains\Order\Events\OrderProcessingStarted;
use App\Domains\Order\Models\Order;
use DomainException;

class ProcessOrder
{
    public function execute(Order $order, ?int $userId = null): Order
    {
        if (!$order->isPaid()) {
            throw new DomainException('Only paid orders can be processed.');
        }

        $order->markAsProcessing('Order processing started', $userId);

        OrderProcessingStarted::dispatch($order);

        return $order->fresh();
    }
}

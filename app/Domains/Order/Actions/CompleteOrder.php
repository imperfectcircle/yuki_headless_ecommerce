<?php

namespace App\Domains\Order\Actions;

use App\Domains\Order\Events\OrderDelivered;
use App\Domains\Order\Models\Order;
use DomainException;

class CompleteOrder
{
    public function execute(Order $order, ?int $userId = null): Order
    {
        if (!$order->isShipped()) {
            throw new DomainException('Only shipped orders can be marked as delivered.');
        }

        $order->markAsDelivered('Order delivered to customer', $userId);

        OrderDelivered::dispatch($order);

        return $order->fresh();
    }
}

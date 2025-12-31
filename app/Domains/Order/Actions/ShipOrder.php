<?php

namespace App\Domains\Order\Actions;

use App\Domains\Order\Events\OrderShipped;
use App\Domains\Order\Models\Order;
use DomainException;

class ShipOrder
{
    public function execute(
        Order $order,
        ?string $trackingNumber = null,
        ?string $carrier = null,
        ?int $userId = null,
    ): Order {
        if (!$order->isFulfilled) {
            throw new DomainException('Only fulfilled orders can be shipped.');
        }

        $note = 'Order shipped';
        if ($trackingNumber) {
            $note .= " - Tracking: {$trackingNumber}";
        }

        if ($carrier) {
            $note .= " via {$carrier}";
        }

        $order->markAsShipped($note, $userId);

        // TODO: Store tracking number info in a separate table if needed
        // For now, it's just in the status history note

        OrderShipped::dispatch($order, $trackingNumber, $carrier);

        return $order->fresh();
    }
}

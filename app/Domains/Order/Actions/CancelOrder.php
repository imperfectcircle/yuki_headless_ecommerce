<?php

namespace App\Domains\Order\Actions;

use App\Domains\Inventory\Actions\ReleaseOrderInventory;
use App\Domains\Order\Events\OrderCancelled;
use App\Domains\Order\Models\Order;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelOrder
{
    public function __construct(
        protected ReleaseOrderInventory $releaseOrderInventory
    ) {}

    public function execute(Order $order, ?string $reason = null, ?int $userId = null): Order
    {
        if ($order->isCancelled()) {
            throw new DomainException('Order is already cancelled.');
        }

        // Cannot cancel completed orders
        if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_REFUNDED])) {
            throw new DomainException('Cannot cancel completed orders. Use refund instead.');
        }

        DB::transaction(function () use ($order, $reason, $userId) {
            // Release inventory if it was reserved or paid
            if (in_array($order->status, [Order::STATUS_RESERVED, Order::STATUS_PAID])) {
                $this->releaseOrderInventory->execute($order);
            }

            // Update order status
            $order->markAsCancelled($reason, $userId);

            // Clear reservation timeout
            if ($order->reserved_until) {
                $order->update(['reserved_until' => null]);
            }

            // Dispatch event
            OrderCancelled::dispatch($order);
        });

        return $order->fresh();
    }
}

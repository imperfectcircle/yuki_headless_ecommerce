<?php

namespace App\Domains\Order\Actions;

use App\Domains\Order\Events\OrderStatusChanged;
use App\Domains\Order\Models\Order;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateOrderStatus
{
    public function execute(
        Order $order,
        string $newStatus,
        ?string $note = null,
        ?int $userId = null,
    ): Order {
        if (!$order->canTransitionTo($newStatus)) {
            throw new DomainException(
                "Cannot transition order from {$order->status} to {$newStatus}"
            );
        }

        DB::transaction(function () use ($order, $newStatus, $note, $userId) {
            $order->transitionTo($newStatus, $note, $userId);

            // Dispatch event for side effects
            //OrderStatusChanged::dispatch($order, $newStatus, $note);
            event(new OrderStatusChanged($order, $newStatus, $note));
        });

        return $order->fresh();
    }
}

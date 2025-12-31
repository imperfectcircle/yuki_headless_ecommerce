<?php

namespace App\Domains\Order\Actions;

use App\Domains\Inventory\Actions\RestockInventory;
use App\Domains\Order\Events\OrderRefunded;
use App\Domains\Order\Models\Order;
use App\Domains\Payments\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;

class RefundOrder
{
    public function execute(
        Order $order,
        ?string $reason = null,
        ?int $userId = null,
        bool $restockInventory = true,
    ): Order {
        // Can only refund paid/delivered orders
        if (!in_array($order->status, [
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_FULFILLED,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED
        ])) {
            throw new DomainException('Order cannot be refunded in its current state.');
        }

        DB::transaction(function () use ($order, $reason, $userId, $restockInventory) {
            // Mark payment as refunded (this is placeholder - actual payment provider refund should happen)
            $payment = Payment::where('order_id', $order->id)
                ->where('status', Payment::STATUS_PAID)
                ->first();

            if ($payment) {
                // TODO: implement actual payment provider refund
                // For now, just update the status
                $payment->update(['status' => Payment::STATUS_REFUNDED]);
            }

            // Restock invnetory if requested
            if ($restockInventory) {
                foreach ($order->items as $item) {
                    $inventory = $item->productVariant->inventory;
                    if ($inventory) {
                        $inventory->increment('quantity', $item->quantity);
                    }
                }
            }

            // Update order status
            $order->markAsRefunded($reason, $userId);

            // Dispatch event
            OrderRefunded::dispatch($order);
        });

        return $order->fresh();
    }
}

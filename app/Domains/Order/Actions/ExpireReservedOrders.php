<?php

namespace App\Domains\Order\Actions;

use App\Domains\Inventory\Actions\ReleaseOrderInventory;
use App\Domains\Order\Events\OrderCancelled;
use App\Domains\Order\Events\OrderReservationExpired;
use App\Domains\Order\Models\Order;
use Illuminate\Support\Facades\DB;

class ExpireReservedOrders
{
    public function __construct(
        protected ReleaseOrderInventory $releaseOrderInventory
    ) {}

    public function execute(): void
    {
        Order::expiredReservations()
            ->chunkById(50, function ($orders) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order) {
                        $this->releaseOrderInventory->execute($order);

                        $order->update([
                            'status' => Order::STATUS_CANCELLED,
                            'reserved_until' => null,
                        ]);

                        OrderReservationExpired::dispatch($order);
                        OrderCancelled::dispatch($order);
                    });
                }
            });
    }
}

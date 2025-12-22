<?php

namespace App\Domains\Order\Actions;

use App\Domains\Inventory\Actions\ReleaseOrderInventory;
use App\Domains\Order\Models\Order;
use Illuminate\Support\Facades\DB;

class ExpireReservedOrders
{
    public function __construct(
        protected ReleaseOrderInventory $releaseOrderInventory
    ) {}

    public function execute(): void
    {
        Order::where('status', Order::STATUS_RESERVED)
            ->where('reserved_until', '<', now())
            ->chunkById(50, function ($orders) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order) {
                        $this->releaseOrderInventory->execute($order);

                        $order->update([
                            'status' => Order::STATUS_CANCELLED,
                            'reserved_until' => null,
                        ]);
                    });
                }
            });
    }
}

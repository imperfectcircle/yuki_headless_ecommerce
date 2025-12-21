<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Order\Models\Order;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReserveOrderInventory
{
    public function __construct(
        protected ReserveInventory $reserveInventory
    ) {}     
    
    public function execute(Order $order): void
    {
        if ($order->status !== Order::STATUS_DRAFT) {
            throw new DomainException(
                'Inventory can only be reserved for draft orders.'
            );
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $this->reserveInventory->execute(
                    $item->productVariant,
                    $item->quantity
                );
            }

            $order->update([
                'status' => Order::STATUS_RESERVED,
            ]);
        });
    }
}

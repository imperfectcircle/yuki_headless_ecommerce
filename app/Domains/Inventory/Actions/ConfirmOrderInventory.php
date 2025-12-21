<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Inventory\Models\Inventory;
use App\Domains\Order\Models\Order;
use DomainException;
use Illuminate\Support\Facades\DB;

class ConfirmOrderInventory
{
    public function execute(Order $order): void
    {
        if ($order->status !== Order::STATUS_RESERVED) {
            throw new DomainException(
                'Inventory can only be confirmed for reserved orders.'
            );
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                /** @var Inventory $inventory */
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($inventory->reserved < $item->quantity) {
                    throw new DomainException(
                        'Reserved inventory inconsistency for variant {$item->product_variant_id}'
                    ); 
                }

                $inventory->decrement('reserved', $item->quantity);
                $inventory->decrement('quantity', $item->quantity);
            }

            $order->update([
                'status' => Order::STATUS_PAID,
            ]);
        });
    }
}

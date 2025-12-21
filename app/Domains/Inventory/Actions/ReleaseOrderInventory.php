<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Inventory\Models\Inventory;
use App\Domains\Order\Models\Order;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReleaseOrderInventory
{
    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                /** @var Inventory $inventory */
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                
                if (!$inventory) {
                    continue; // invetory missing = nothing to release
                }

                if ($inventory->reserved < $item->quantity) {
                    throw new DomainException(
                        "Cannot release more inventory than is reserved for variant {$item->product_variant_id}"
                    ); 
                }

                $inventory->decrement('reserved', $item->quantity);
            }
        });
    }
}

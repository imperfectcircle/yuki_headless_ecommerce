<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Inventory\Models\Inventory;
use App\Domains\Order\Models\Order;
use Illuminate\Support\Facades\DB;

class ConfirmInventory
{
    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                /** @var Inventory $inventory */
                $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $inventory->decrement('reserved', $item->quantity);
                $inventory->decrement('quantity', $item->quantity);
            }
        });
    }
}

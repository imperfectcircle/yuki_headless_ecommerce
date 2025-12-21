<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Catalog\Models\ProductVariant;
use App\Domains\Inventory\Models\Inventory;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReleaseInventory
{
    public function execute(ProductVariant $variant, int $quantity): Inventory
    {
        if ($quantity <= 0) {
            throw new DomainException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($variant, $quantity) {
            /** @var Inventory $inventory */
            $inventory = Inventory::where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($inventory->reserved === 0) {
                return $inventory;
            }

            if ($quantity >= $inventory->reserved) {
                $inventory->update(['reserved' => 0]);
                return $inventory->fresh();
            }

            $inventory->decrement('reserved', $quantity);
            return $inventory->fresh();
        });
    }
}

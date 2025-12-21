<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Catalog\Models\ProductVariant;
use App\Domains\Inventory\Models\Inventory;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReserveInventory
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

            $available = $inventory->quantity - $inventory->reserved;

            // If stock is availabele, reserve normally
            if ($available >= $quantity) {
                $inventory->increment('reserved', $quantity);
                return $inventory->fresh();
            }

            // If stock is not sufficient, check backorder policy
            if (!$variant->product->backorder_enabled) {
                throw new DomainException('Insufficient stock and backorders are not allowed for this product.');
            }

            // Backorders are allowed, reserve the requested quantity
            $inventory->increment('reserved', $quantity);
            return $inventory->fresh();
        });
    }
}

<?php

namespace App\Domains\Cart\Actions;

use App\Domains\Cart\Models\Cart;
use App\Domains\Catalog\Models\ProductVariant;
use DomainException;
use Illuminate\Support\Facades\DB;

class AddItemToCart
{
    public function execute(Cart $cart, int $variantId, int $quantity): void
    {
        if (!$cart->isActive()) {
            throw new DomainException('Cart is not active.');
        }

        if ($quantity <= 0) {
            throw new DomainException('Quantity must be greater than zero.');
        }

        DB::transaction(function () use ($cart, $variantId, $quantity){

            $variant = ProductVariant::query()
                ->with(['product', 'prices'])
                ->findOrFail($variantId);

            $price = $variant->priceForCurrency($cart->currency);

            if (!$price) {
                throw new DomainException('Price not available for this variant in ' . $cart->currency);
            }

            $existingItem = $cart->items()
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $quantity,
                    'unit_price' => $price->amount,
                ]);
                return;
            }

            $cart->items()->create([
                'product_variant_id' => $variant->id,
                'unit_price' => $price->amount,
                'quantity' => $quantity
            ]);
        });
    }
}

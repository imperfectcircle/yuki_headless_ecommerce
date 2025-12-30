<?php

namespace App\Domains\Cart\Actions;

use App\Domains\Cart\Models\Cart;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateCartItem
{
    public function execute(Cart $cart, int $itemId, int $quantity): void
    {
        if (!$cart->isActive()) {
            throw new DomainException('Cart is not active.');
        }

        if ($quantity < 0) {
            throw new DomainException('Quantity cannot be negative.');
        }

        DB::transaction(function () use ($cart, $itemId, $quantity) {
            $item = $cart->items()
                ->where('id', $itemId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                throw new DomainException('Cart item not found.');
            }

            if ($quantity === 0) {
                $item->delete();
                return;
            }

            $variant = $item->productVariant;
            $price = $variant->priceForCurrency($cart->currency);

            if (!$price) {
                throw new DomainException('Price not available for this variant.');
            }

            $item->update([
                'quantity' => $quantity,
                'unit_price' => $price->amount,
            ]);
        });
    } 
}

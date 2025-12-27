<?php

namespace App\Domains\Cart\Actions;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
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

            $item->update([
                'quantity' => $quantity,
            ]);
        });
    } 
}

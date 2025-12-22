<?php

namespace App\Domains\Cart\Actions;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use DomainException;

class UpdateCartItem
{
    public function execute(Cart $cart, CartItem $item, int $quantity): void
    {
        if (!$cart->isActive()) {
            throw new DomainException('Cart is not active.');
        }

        if ($item->cart_id !== $cart->id) {
            throw new DomainException('Cart item does not belong to this cart.');
        }

        if ($quantity <= 0) {
            throw new DomainException('Quantity must be greater than zero.');
        }

        $item->update(['quantity' => $quantity]);
    } 
}

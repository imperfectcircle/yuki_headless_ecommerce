<?php

namespace App\Domains\Cart\Actions;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use DomainException;

class RemoveCartItem
{
    public function execute(Cart $cart, CartItem $item): void
    {
        if (!$cart->isActive()) {
            throw new DomainException('Cart is not active.');
        }

        if ($item->cart_id !== $cart->id) {
            throw new DomainException('Cart item does not belong to this cart.');
        }

        $item->delete();
    }
}

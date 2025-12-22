<?php

namespace App\Domains\Cart\Actions;

use App\Domains\Cart\Models\Cart;
use App\Domains\Catalog\Models\ProductVariant;
use DomainException;

class AddItemToCart
{
    public function execute(Cart $cart, ProductVariant $variant, int $quantity): void
    {
        if (!$cart->isActive()) {
            throw new DomainException('Cart is not active.');
        }

        if ($quantity <= 0) {
            throw new DomainException('Quantity must be greater than zero.');
        }

        $item = $cart->items()
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($item) {
            $item->increment('quantity', $quantity);
            return;
        }

        $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }
}

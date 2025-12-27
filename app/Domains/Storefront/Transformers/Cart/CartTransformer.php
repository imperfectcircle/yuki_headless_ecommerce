<?php

namespace App\Domains\Storefront\Transformers\Cart;

use App\Domains\Cart\Models\Cart;
use App\Domains\Storefront\DTOs\Cart\StorefrontCartDTO;
use App\Domains\Storefront\DTOs\Cart\StorefrontCartItemDTO;
use App\Domains\Storefront\DTOs\Cart\StorefrontCartTotalsDTO;

final class CartTransformer
{
    public function transform(Cart $cart): StorefrontCartDTO
    {
        $items = [];
        $subtotal = 0;

        foreach ($cart->items as $item) {
            $lineTotal = $item->unit_price * $item->quantity;
            $subtotal += $lineTotal;

            $items[] = new StorefrontCartItemDTO(
                id: $item->id,
                productId: $item->product_id,
                variantId: $item->product_variant_id,
                name: $item->product_name,
                sku: $item->sku,
                quantity: $item->quantity,
                unitPrice: $item->unit_price,
                total: $lineTotal,
            );
        }

        $totals = new StorefrontCartTotalsDTO(
            subtotal: $subtotal,
            tax: 0,
            grandTotal: $subtotal
        );

        return new StorefrontCartDTO(
            id: $cart->id,
            token: $cart->token,
            currency: $cart->currency,
            items: $items,
            totals: $totals,
        );
    }
}

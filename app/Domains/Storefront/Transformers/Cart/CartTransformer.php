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
        $taxTotal = 0;

        foreach ($cart->items as $item) {
            $variant = $item->ProductVariant;
            $product = $variant->product;
            
            $lineTotal = $item->unit_price * $item->quantity;

            // Calculate tax if needed (placeholder for now)
            $lineTax = 0;

            $subtotal += $lineTotal;
            $taxTotal += $lineTax;

            $items[] = new StorefrontCartItemDTO(
                id: $item->id,
                productId: $product->id,
                variantId: $variant->id,
                name: $product->name,
                sku: $variant->sku,
                quantity: $item->quantity,
                unitPrice: $item->unit_price,
                total: $lineTotal,
            );
        }

        $totals = new StorefrontCartTotalsDTO(
            subtotal: $subtotal,
            tax: $taxTotal,
            grandTotal: $subtotal + $taxTotal,
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

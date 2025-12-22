<?php

namespace App\Domains\Order\Actions;

use App\Domains\Cart\Models\Cart;
use App\Domains\Inventory\Actions\ReserveOrderInventory;
use App\Domains\Order\Models\Order;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateOrderFromCart
{
    public function execute(Cart $cart): Order
    {
        if (!$cart->isActive()) {
            throw new DomainException('Cart cannot be checked out.');
        }

        return DB::transaction(function () use ($cart) {
            $order = Order::create([
                'status' => Order::STATUS_DRAFT,
                'currency' => $cart->currency,
            ]);

            $subtotal = 0;

            foreach ($cart->items as $item) {
                $variant = $item->productVariant;

                $price = $variant->priceForCurrency($cart->currency);

                if (!$price) {
                    throw new DomainException("Price not available for variant {$variant->id}");
                }

                $lineTotal = $price->amount * $item->quantity;

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $variant->product->name,
                    'attributes' => $variant->attributes,
                    'unit_price' => $price->amount,
                    'quantity' => $item->quantity,
                    'total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax_total' => 0, // placeholder: calculated later
                'shipping_total' => 0, // placeholder: calculated later
                'grand_total' => $subtotal,
            ]);

            app(ReserveOrderInventory::class)->execute($order);

            $order->update([
                'status' => Order::STATUS_RESERVED,
            ]);

            $cart->markAsConverted();

            return $order;
        });
    }
}

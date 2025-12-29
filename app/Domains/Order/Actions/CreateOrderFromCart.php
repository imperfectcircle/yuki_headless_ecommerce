<?php

namespace App\Domains\Order\Actions;

use App\Domains\Cart\Models\Cart;
use App\Domains\Inventory\Actions\ReserveOrderInventory;
use App\Domains\Order\Events\OrderCreated;
use App\Domains\Order\Models\Order;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateOrderFromCart
{
    public function execute(Cart $cart): Order
    {
        if (!$cart->isActive()) {
            throw new DomainException('Cart cannot be converted to order.');
        }

        if ($cart->items->isEmpty()) {
            throw new DomainException('Cannot create order from empty cart.');
        }

        return DB::transaction(function () use ($cart) {
            $order = Order::create([
                'number' => $this->generateOrderNumber(),
                'status' => Order::STATUS_DRAFT,
                'currency' => $cart->currency,
                'subtotal' => 0,
                'tax_total' => 0,
                'shipping_total' => 0,
                'grand_total' => 0,
            ]);

            $subtotal = 0;

            foreach ($cart->items as $item) {
                $variant = $item->productVariant;

                $price = $variant->priceForCurrency($cart->currency);

                if (!$price) {
                    throw new DomainException("Price not available for variant {$variant->id} in {$cart->currency}");
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
                'grand_total' => $subtotal,
            ]);

            $cart->markAsConverted();

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    protected function generateOrderNumber(): string
    {
        $prefix = config('orders.number_prefix', 'ORD');
        $year = now()->format('Y');
        $random = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$random}";
    }
}

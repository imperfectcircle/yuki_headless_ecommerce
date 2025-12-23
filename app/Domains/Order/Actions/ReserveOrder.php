<?php

namespace App\Domains\Order\Actions;

use App\Domains\Inventory\Actions\ReserveOrderInventory;
use App\Domains\Order\Models\Order;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReserveOrder
{
    public function execute(Order $order): Order
    {
        if ($order->status !== Order::STATUS_DRAFT) {
            throw new DomainException('Only draft orders can be reserved.');
        }

        if ($order->items->isEmpty()) {
            throw new DomainException('Cannot reserve an empty order.');
        }

        return DB::transaction(function () use ($order){
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($order->items as $item) {
                $variant = $item->productVariant;

                $price = $variant->priceForCurrency($order->currency);

                if (!$price) {
                    throw new DomainException("Price not available for vartiant {$variant->id}");
                }

                $lineTotal = $price->amount * $item->quantity;
                $lineTax = 0; // placeholder

                $item->update([
                    'unit_price' => $price->amount,
                    'total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }

            // placeholder: shipping rules future
            $shippingTotal = 0;

            $order->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'shipping_total' => $shippingTotal,
                'grand_total' => $subtotal + $taxTotal + $shippingTotal,
            ]);

            app(ReserveOrderInventory::class)->execute($order);

            $order->update([
                'status' => Order::STATUS_RESERVED,
            ]);

            return $order;
        });
    }
}

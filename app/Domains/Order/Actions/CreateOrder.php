<?php

// @deprecated (for now)
// Used for direct API or admin use cases

namespace App\Domains\Order\Actions;

use App\Domains\Catalog\Models\ProductVariant;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    public static function execute(
        array $items,
        string $currencyCode,
        string $customerEmail,
        ?string $customerName = null
    ): Order {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'Order must contain at least one item.']);
        }

        return DB::transaction(function () use ($items, $currencyCode, $customerEmail, $customerName) {
            $order = Order::create([
                'number' => self::generateOrderNumber(),
                'status' => 'pending',
                'currency' => $currencyCode,
                'subtotal' => 0,
                'tax_total' => 0,
                'shipping_total' => 0,
                'grand_total' => 0,
                'customer_email' => $customerEmail,
                'customer_name' => $customerName,
            ]);

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($items as $item) {
                $variant = ProductVariant::query()
                    ->where('id', $item['variant_id'])
                    ->where('is_active', true)
                    ->firstOrFail();

                $price = $variant->prices()
                    ->where('currency', $currencyCode)
                    ->where(function ($q) {
                        $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
                    })
                    ->first();

                    if (!$price) {
                        throw ValidationException::withMessages([
                            'price' => "No valid price for variant {$variant->id} in {$currencyCode}",
                        ]);
                    }

                    $quantity = (int) $item['quantity'];
                    if ($quantity <= 0) {
                        throw ValidationException::withMessages([
                            'quantity' => 'Quantity must be grater than zero.',
                        ]);
                    }

                    $lineTotal = $price->amount * $quantity;
                    $lineTax = (int) round($lineTotal * ($price->vat_rate / 100));

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_variant_id' => $variant->id,
                        'sku' => $variant->sku,
                        'name' => $variant->product->name,
                        'attributes' => $variant->attributes,
                        'unit_price' => $price->amount,
                        'quantity' => $quantity,
                        'total' => $lineTotal,  
                    ]);

                    $subtotal += $lineTotal;
                    $taxTotal += $lineTax;
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $subtotal + $taxTotal,
            ]);

            return $order;
        });
    }

    protected static function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('Y') . '-' . str_pad(
            (string) random_int(1, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}

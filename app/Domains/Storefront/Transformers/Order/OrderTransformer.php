<?php

namespace App\Domains\Storefront\Transformers\Order;

use App\Domains\Order\Models\Order;
use App\Domains\Storefront\DTOs\Order\StorefrontOrderDTO;

final class OrderTransformer
{
    public function transform(Order $order): StorefrontOrderDTO
    {
        return new StorefrontOrderDTO(
            id: $order->id,
            status: $order->status,
            subtotal: $order->subtotal,
            taxTotal: $order->tax_total,
            shippingTotal: $order->shipping_total,
            grandTotal: $order->grand_total,
            currency: $order->currency
        );
    }
}

<?php

namespace App\Domains\Order\Transformers;

use App\Domains\Order\DTOs\OrderListItemDTO;
use App\Domains\Order\Models\Order;
use Illuminate\Support\Collection;

class OrderListTransformer
{
    public static function transform(Order $order): OrderListItemDTO
    {
        return new OrderListItemDTO(
            id: $order->id,
            number: $order->number,
            status: $order->status,
            customerEmail: $order->customer_email,
            customerFullName: $order->customer_full_name,
            grandTotal: $order->grand_total,
            currency: $order->currency,
            itemsCount: $order->items_count ?? $order->items->count(),
            createdAt: $order->created_at->toIso8601String(),
            reservedUntil: $order->reserved_until?->toIso8601String(),
        );
    }

    public static function transformCollection(Collection $orders): array
    {
        return $orders->map(fn($order) => self::transform($order)->toArray())->toArray();
    }
}


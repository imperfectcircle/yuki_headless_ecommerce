<?php

namespace App\Domains\Order\Transformers;

use App\Domains\Order\Dtos\OrderDetailDto;
use App\Domains\Order\Dtos\OrderItemDTO;
use App\Domains\Order\Dtos\OrderTimelineEventDTO;
use App\Domains\Order\Models\Order;

class OrderDetailTransformer
{
    public function transform(Order $order): OrderDetailDto
    {
        $items = $order->items->map(function ($item) {
            return new OrderItemDTO(
                id: $item->id,
                productVariantId: $item->product_variant_id,
                sku: $item->sku,
                name: $item->name,
                attributes: $item->attributes,
                unitPrice: $item->unit_price,
                quantity: $item->quantity,
                total: $item->total,
            );
        })->toArray();

        $timeline = self::buildTimeline($order);

        return new OrderDetailDto(
            id: $order->id,
            number: $order->number,
            status: $order->status,
            currency: $order->currency,
            subtotal: $order->subtotal,
            taxTotal: $order->tax_total,
            shippingTotal: $order->shipping_total,
            grandTotal: $order->grand_total,
            customerEmail: $order->customer_email,
            customerFullName: $order->customer_full_name,
            customerPhone: $order->customer_phone,
            shippingAddress: $order->shipping_address,
            billingAddress: $order->billing_address,
            guestCheckout: $order->guest_checkout,
            customerProfileId: $order->customer_profile_id,
            items: $items,
            timeline: $timeline,
            reservedUntil: $order->reserved_until?->toIso8601String(),
            createdAt: $order->created_at->toIso8601String(),
            updatedAt: $order->updated_at->toIso8601String(),
        );
    }

    public static function buildTimeline(Order $order): array
    {
        $events = [];

        $events[] = new OrderTimelineEventDTO(
            type: 'order_created',
            status: Order::STATUS_DRAFT,
            message: 'Order created',
            userName: null,
            metadata: ['is_guest' => $order->guest_checkout],
            createdAt: $order->created_at->toIso8601String(),
        );

        if ($order->relationLoaded('statusHistory')) {
            foreach ($order->statusHistory as $history) {
                $vents[] = new OrderTimelineEventDTO(
                    type: 'status_change',
                    status: $history->to_status,
                    message: self::getStatusChangeMessage($history->from_status, $history->to_status),
                    userName: $history->user?->name,
                    metadata: [
                        'from' => $history->from_status,
                        'to' => $history->to_status,
                        'note' => $history->note,
                    ],
                    createdAt: $history->created_at->toIso8601String(),
                );
            }
        }

        if ($order->relationLoaded('payments')) {
            foreach ($order->payments as $payment) {
                $events[] = new OrderTimelineEventDTO(
                    type: 'payment',
                    status: $payment->status,
                    message: self::getPaymentMessage($payment->status, $payment->provider),
                    userName: null,
                    metadata: [
                        'provider' => $payment->provider,
                        'amount' => $payment->amount,
                        'reference' => $payment->provider_reference,
                    ],
                    createdAt: $payment->created_at->toIso8601String(),
                );
            }
        }

        usort($events, function ($a, $b) {
            return strcmp($a->createdAt, $b->createdAt);
        });

        return $events;
    }

    private static function getStatusChangeMessage(string $from, string $to): string
    {
        return match ($to) {
            Order::STATUS_RESERVED => 'Inventory reserved',
            Order::STATUS_PAID => 'Payment completed',
            Order::STATUS_PROCESSING => 'Order processing started',
            Order::STATUS_FULFILLED => 'Order fulfilled',
            Order::STATUS_SHIPPED => 'Order shipped',
            Order::STATUS_DELIVERED => 'Order delivered',
            Order::STATUS_CANCELLED => 'Order cancelled',
            Order::STATUS_REFUNDED => 'Order refunded',
            default => "Status changed from {$from} to {$to}",
        };
    }

    private static function getPaymentMessage(string $status, string $provider): string
    {
        return match ($status) {
            'pending' => "Payment initiated via {$provider}",
            'paid' => "Payment completed via {$provider}",
            'failed' => "Payment failed via {$provider}",
            'refunded' => "Payment refunded via {$provider}",
            default => "Payment {$status} via {$provider}",
        };
    }
}
<?php

namespace App\Domains\Order\Queries;

use App\Domains\Order\DTOs\OrderDetailDTO;
use App\Domains\Order\DTOs\OrderItemDTO;
use App\Domains\Order\DTOs\OrderTimelineEventDTO;
use App\Domains\Order\Models\Order;

class GetOrderDetail
{
    public function execute(int $orderId): OrderDetailDTO
    {
        $order = Order::query()
            ->withRelations()
            ->findOrFail($orderId);

        return $this->transform($order);
    }

    public function executeByNumber(string $orderNumber): OrderDetailDTO
    {
        $order = Order::query()
            ->withRelations()
            ->where('number', $orderNumber)
            ->firstOrFail();

        return $this->transform($order);
    }

    public function executeForCustomer(int $orderId, int $customerProfileId): OrderDetailDTO
    {
        $order = Order::query()
            ->withRelations()
            ->where('id', $orderId)
            ->where('customer_profile_id', $customerProfileId)
            ->firstOrFail();

        return $this->transform($order);
    }

    public function executeForGuestByNumberAndEmail(string $orderNumber, string $email): OrderDetailDTO
    {
        $order = Order::query()
            ->withRelations()
            ->where('number', $orderNumber)
            ->where('customer_email', $email)
            ->where('guest_checkout', true)
            ->firstOrFail();

        return $this->transform($order);
    }

    // Transform items
    private function transform(Order $order): OrderDetailDTO
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

        // Build timeline from status history and payments
        $timeline = $this->buildTimeline($order);

        return new OrderDetailDTO(
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

    private function buildTimeline(Order $order): array
    {
        $events = [];

        // Add order creation event

        $events[] = new OrderTimelineEventDTO(
            type: 'order_created',
            status: $order->status,
            message: 'Order created',
            userName: null,
            metadata: [
                'is_guest' => $order->guest_checkout,
            ],
            createdAt: $order->created_at->toIso8601String(),
        );

        // Add status history events
        foreach ($order->statusHistory as $history) {
            $events[] = new OrderTimelineEventDTO(
                type: 'status_change',
                status: $history->to_status,
                message: "Status changed from {$history->from_status} to {$history->to_status}",
                userName: $history->user?->name,
                metadata: [
                    'from' => $history->from_status,
                    'to' => $history->to_status,
                    'note' => $history->note,
                ],
                createdAt: $history->created_at->toIso8601String(),
            );
        }

        // Add payment events
        foreach ($order->payments as $payment) {
            $events[] = new OrderTimelineEventDTO(
                type: 'payment',
                status: $payment->status,
                message: "Payment {$payment->status} via {$payment->provider}",
                userName: null,
                metadata: [
                    'provider' => $payment->provider,
                    'amount' => $payment->amount,
                    'reference' => $payment->provider_reference,
                ],
                createdAt: $payment->created_at->toIso8601String(),
            );
        }

        // Sort by date
        usort($events, function ($a, $b) {
            return strcmp($a->createdAt, $b->createdAt);
        });

        return $events;
    }
}

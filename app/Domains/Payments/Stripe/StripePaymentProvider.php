<?php

namespace App\Domains\Payments\Stripe;

use App\Domains\Order\Models\Order;
use App\Domains\Payments\Contracts\PaymentProvider;
use App\Domains\Payments\DTOs\PaymentIntentData;
use App\Domains\Payments\DTOs\PaymentWebhookData;
use Stripe\StripeClient;
use Stripe\Webhook;

final class StripePaymentProvider implements PaymentProvider
{
    public function __construct(
        protected StripeClient $stripe
    ) {}

    public function code(): string
    {
        return 'stripe';
    }

    public function createPayment(Order $order): PaymentIntentData
    {
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'currency' => strtolower($order->currency),
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($order->currency),
                        'unit_amount' => $order->grand_total,
                        'product_data' => [
                            'name' => 'Order ' . $order->id,
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'success_url' => config('services.stripe.success_url'),
            'cancel_url' => config('services.stripe.cancel_url'),
        ]);

        return new PaymentIntentData(
            provider: $this->code(),
            providerReference: $session->id,
            redirectUrl: $session->url,
            rawPayload: $session->toArray(),
        );
    }

    public function parseWebhook(string $payload, array $headers): PaymentWebhookData
    {
        $event = Webhook::constructEvent(
            $payload,
            $headers['stripe-signature'] ?? '',
            config('services.stripe.webhook_secret')
        );

        return match ($event->type) {
            'checkout.session.completed' => new PaymentWebhookData(
                provider: $this->code(),
                providerReference: $event->data->object->id,
                eventType: 'paid',
                rawPayload: $event->toArray(),
            ),
            default => new PaymentWebhookData(
                provider: $this->code(),
                providerReference: $event->data->object->id ?? '',
                eventType: 'unknown',
                rawPayload: $event->toArray(),
            ),
        };
    }
}
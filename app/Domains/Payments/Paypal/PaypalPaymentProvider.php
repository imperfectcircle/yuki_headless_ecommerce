<?php

namespace App\Domains\Payments\Paypal;

use App\Domains\Order\Models\Order;
use App\Domains\Payments\Contracts\PaymentProvider;
use App\Domains\Payments\DTOs\PaymentIntentData;
use App\Domains\Payments\DTOs\PaymentWebhookData;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

final class PaypalPaymentProvider implements PaymentProvider
{
    public function __construct(
        protected PayPalHttpClient $client
    ) {}

    public function code(): string
    {
        return 'paypal';
    }

    public function createPayment(Order $order): PaymentIntentData
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return-representation');

        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $order->id,
                'amount' => [
                    'currency_code' => $order->currency,
                    'value' => number_format($order->grand_total / 100, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => config('services.paypal.success_url'),
                'cancel_url' => config('services.paypal.cancel_url'),
            ],
        ];

        $response = $this->client->execute($request);

        $approvalLink = collect($response->result->links)
            ->firstWhere('rel', 'approve')
            ->href;

        return new PaymentIntentData(
            provider: $this->code(),
            providerReference: $response->result->id,
            redirectUrl: $approvalLink,
            rawPayload: json_decode(json_encode($response->result), true),
        );
    }

    public function parseWebhook(string $payload, array $headers): PaymentWebhookData
    {
        $data = json_decode($payload, true);

        // ⚠️ TODO: Verify webhook signature via PayPal API
        // https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature

        return match ($data['event_type'] ?? null) {
            'CHECKOUT.ORDER.APPROVED',
            'PAYMENT.CAPTURE.COMPLETED' => new PaymentWebhookData(
                provider: $this->code(),
                providerReference: $data['resource']['id'] ?? '',
                eventType: 'paid',
                rawPayload: $data,
            ),
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.REFUNDED' => new PaymentWebhookData(
                provider: $this->code(),
                providerReference: $data['resource']['id'] ?? '',
                eventType: 'failed',
                rawPayload: $data,
            ),
            default => new PaymentWebhookData(
                provider: $this->code(),
                providerReference: $data['resource']['id'] ?? '',
                eventType: 'unknown',
                rawPayload: $data,
            ),
        };
    }
}

<?php

namespace App\Http\Controllers\Webhooks;

use App\Domains\Payments\Actions\HandleFailedPayment;
use App\Domains\Payments\Actions\HandleSuccessfulPayment;
use App\Domains\Payments\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function handle(
        Request $request,
        HandleSuccessfulPayment $handleSuccessfulPayment,
        HandleFailedPayment $handleFailedPayment
    ): Response {
        $payload = $request->all();

        $eventType = $payload['type'] ?? null;
        $data = $payload['data']['object'] ?? null;

        if (!$eventType || $data) {
            return response()->noContent();
        }

        // Stripe PaymentIntent ID
        $providerReference = $data['id'] ?? null;

        if (!$providerReference) {
            return response()->noContent();
        }

        /** @var Payment|null $payment */
        $payment = Payment::where('provider', 'stripe')
            ->where('provider_reference', $providerReference)
            ->first();

        if (!$payment) {
            return response()->noContent();
        }

        match ($eventType) {
            'payment_intent.succeeded' => $handleSuccessfulPayment->execute($payment),
            'payment_intent.payment_failed',
            'payment_intent.canceled' => $handleFailedPayment->execute($payment),

            default => null,
        };

        return response()->noContent();
    }
}

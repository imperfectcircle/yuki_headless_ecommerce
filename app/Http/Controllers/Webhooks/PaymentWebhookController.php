<?php

namespace App\Http\Controllers\Webhooks;

use App\Domains\Payments\Actions\HandleFailedPayment;
use App\Domains\Payments\Actions\HandleSuccessfulPayment;
use App\Domains\Payments\Contracts\PaymentProvider;
use App\Domains\Payments\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected HandleSuccessfulPayment $handleSuccessfulPayment,
        protected HandleFailedPayment $handleFailedPayment,
    ) {}

    public function __invoke(Request $request, string $provider): Response
    {
        $paymentProvider = app(PaymentProvider::class, ['provider' => $provider]);

        try {    
            $webhookData = $paymentProvider->parseWebhook(
                $request->getContent(),
                $request->headers->all()
            );

            Log::info('Payment webhook received', [
                'provider' => $webhookData->provider,
                'reference' => $webhookData->providerReference,
                'event' => $webhookData->eventType,
            ]);

            if ($webhookData->eventType === 'unknown') {
                return response()->noContent();
            }

            $payment = Payment::where('provider', $webhookData->provider)
                ->where('provider_reference', $webhookData->providerReference)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$payment) {
                Log::warning('Payment not found for webhook', [
                    'provider' => $webhookData->provider,
                    'reference' => $webhookData->providerReference
                ]);
                return response()->noContent();
            }

            match ($webhookData->eventType) {
                'paid'   => $this->handleSuccessfulPayment->execute($payment),
                'failed' => $this->handleFailedPayment->execute($payment),
                default  => null,
            };

            return response()->noContent();
        
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->noContent();
        }
    }
}
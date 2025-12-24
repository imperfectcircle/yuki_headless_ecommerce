<?php

namespace App\Http\Controllers\Webhooks;

use App\Domains\Payments\Actions\HandleFailedPayment;
use App\Domains\Payments\Actions\HandleSuccessfulPayment;
use App\Domains\Payments\Contracts\PaymentProvider;
use App\Domains\Payments\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentProvider $paymentProvider,
        protected HandleSuccessfulPayment $handleSuccessfulPayment,
        protected HandleFailedPayment $handleFailedPayment,
    ) {}

    public function __invoke(Request $request): Response
    {
        $webhookData = $this->paymentProvider->parseWebhook(
            $request->getContent(),
            $request->headers->all()
        );

        if ($webhookData->eventType === 'unknown') {
            return response()->noContent();
        }

        $payment = Payment::where('provider', $webhookData->provider)
            ->where('provider_reference', $webhookData->providerReference)
            ->lockForUpdate()
            ->firstOrFail();

        match ($webhookData->eventType) {
            'paid'   => $this->handleSuccessfulPayment->execute($payment),
            'failed' => $this->handleFailedPayment->execute($payment),
            default  => null,
        };

        return response()->noContent();
    }
}
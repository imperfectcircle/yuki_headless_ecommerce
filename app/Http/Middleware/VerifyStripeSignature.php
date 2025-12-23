<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class VerifyStripeSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $payload = $request->getContent();
        $signatureHeader = $request->header('Stripe-Signature');

        if (!$signatureHeader) {
            abort(400, 'Missing Stripe signature header.');
        }

        try {
            Webhook::constructEvent(
                $payload,
                $signatureHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (UnexpectedValueException) {
            // Invalid payload
            abort(400, 'Invalid Stripe payload.');
        } catch (SignatureVerificationException) {
            // Invalid signature
            abort(400, 'Invalid Stripe signature.');
        }

        return $next($request);
    }
}

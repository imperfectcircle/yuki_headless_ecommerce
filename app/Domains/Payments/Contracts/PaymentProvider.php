<?php

namespace App\Domains\Payments\Contracts;

use App\Domains\Order\Models\Order;
use App\Domains\Payments\DTOs\PaymentIntentData;
use App\Domains\Payments\DTOs\PaymentWebhookData;

interface PaymentProvider
{
    /**
    * Create a payment intent / payment session
    */
    public function createPayment(Order $order): PaymentIntentData;

    /**
    * Verify and parse an incoming webhook payload
    */
    public function parseWebhook(string $payload, array $headers): PaymentWebhookData;

    public function code(): string;
}

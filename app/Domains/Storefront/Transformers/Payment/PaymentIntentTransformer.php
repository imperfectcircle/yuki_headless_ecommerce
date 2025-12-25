<?php

namespace App\Domains\Storefront\Transformers\Payment;

use App\Domains\Payments\DTOs\PaymentIntentData;
use App\Domains\Storefront\DTOs\Payment\StorefrontPaymentIntentDTO;

final class PaymentIntentTransformer
{
    public function transform(
        PaymentIntentData $intent
    ): StorefrontPaymentIntentDTO {
        return new StorefrontPaymentIntentDTO(
            provider: $intent->provider,
            redirectUrl: $intent->redirectUrl
        );
    }
}

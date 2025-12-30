<?php

namespace App\Domains\Payments\Resolvers;

use App\Domains\Payments\Contracts\PaymentProvider;
use App\Domains\Payments\Models\PaymentProviderConfig;
use InvalidArgumentException;

final class PaymentProviderResolver
{
    /**
    *@param array<string, class-string<PaymentProvider>> $providers
    */
    public function __construct(
        protected array $providers
    ) {}

    public function resolve(string $provider): PaymentProvider
    {
        $config = PaymentProviderConfig::query()
            ->where('code', $provider)
            ->where('enabled', true)
            ->first();

        if (!$config) {
            throw new InvalidArgumentException(
                "Payment provider [{$provider}] is disabled or not configured."
            );
        }

        if (!isset($this->providers[$provider])) {
            throw new InvalidArgumentException("Unknown payment provider [{$provider}].");
        }

        return app($this->providers[$provider]);
    }

    public function default(): PaymentProvider
    {
        $config = PaymentProviderConfig::query()
            ->where('enabled', true)
            ->orderBy('position')
            ->firstOrFail();

        return $this->resolve($config->code);
    }
}

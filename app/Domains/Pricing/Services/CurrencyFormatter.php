<?php

namespace App\Domains\Pricing\Services;

use NumberFormatter;

class CurrencyFormatter
{
    protected string $locale;

    public function __construct(?string $locale = null)
    {
        $this->locale = $locale ?? app()->getLocale();
    }

    public function format(int $amount, string $currencyCode): string
    {
        $formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency(
            $amount / 100,
            $currencyCode
        );
    }

    public function formatWithoutSymbol(int $amount): string
    {
        $formatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);

        return $formatter->format($amount / 100);
    }

    public function parse(string $formatted): int
    {
        $formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);

        $value = $formatter->parse($formatted);

        return (int) round($value * 100);
    }
}

<?php

namespace App\Domains\Pricing\Services;

use NumberFormatter;

class CurrencyFormatter
{
    public function format(int $amount, string $currencyCode): string
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount / 100, $currencyCode);
    }

    public function formatWithoutSymbol(int $amount): string
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
        return $formatter->format($amount / 100);
    }

    public function parse(string $formatted): int
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $value = $formatter->parse($formatted);
        return (int) round($value * 100);
    }
}

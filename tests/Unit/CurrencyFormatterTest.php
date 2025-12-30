<?php

namespace Tests\Unit;

use App\Domains\Pricing\Services\CurrencyFormatter;
use Tests\TestCase;

class CurrencyFormatterTest extends TestCase
{
    public function test_it_formats_currency_with_symbol()
    {
        $formatter = new CurrencyFormatter();

        $result = $formatter->format(999, 'USD');

        $this->assertEquals('$9.99', $result);
    }

    public function test_it_formats_currency_without_symbol()
    {
        $formatter = new CurrencyFormatter();

        $result = $formatter->formatWithoutSymbol(12345);

        $this->assertEquals('123.45', $result);
    }

    public function test_it_parses_formatted_currency()
    {
        $formatter = new CurrencyFormatter();

        $result = $formatter->parse('$19.99');

        $this->assertEquals(1999, $result);
    }
}

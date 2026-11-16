<?php

namespace Tests\Unit;

use App\Services\OrderPricer;
use App\Support\Rupiah;
use PHPUnit\Framework\TestCase;

class RupiahAndDiscountTest extends TestCase
{
    public function test_formats_rupiah_without_decimals(): void
    {
        $this->assertSame('Rp13.000', Rupiah::format(13000));
        $this->assertSame('Rp0', Rupiah::format(0));
        $this->assertSame('Rp1.250.000', Rupiah::format(1250000));
        $this->assertSame('-Rp5.000', Rupiah::format(-5000));
    }

    public function test_calculates_nominal_and_percent_discounts(): void
    {
        $pricer = new OrderPricer;

        $this->assertSame(5000, $pricer->discount(20000, 'nominal', 5000));
        $this->assertSame(20000, $pricer->discount(20000, 'nominal', 50000), 'nominal tidak boleh melebihi subtotal');
        $this->assertSame(2000, $pricer->discount(20000, 'percent', 10));
        $this->assertSame(20000, $pricer->discount(20000, 'percent', 150), 'persen dibatasi 100');
        $this->assertSame(3333, $pricer->discount(33333, 'percent', 10), 'dibulatkan ke bawah');
        $this->assertSame(0, $pricer->discount(20000, null, 10));
    }
}

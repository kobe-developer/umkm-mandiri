<?php

namespace App\Support;

final class Rupiah
{
    /** Format Rupiah tanpa desimal, misal Rp13.000 */
    public static function format(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';

        return $sign.'Rp'.number_format(abs($amount), 0, ',', '.');
    }
}

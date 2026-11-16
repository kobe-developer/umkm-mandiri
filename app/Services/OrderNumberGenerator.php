<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Nomor order unik per hari: PREFIX-YYYYMMDD-NNNN. Harus dipanggil di dalam transaksi. */
class OrderNumberGenerator
{
    public function next(CarbonInterface $date): string
    {
        $day = $date->toDateString();

        DB::table('order_sequences')->insertOrIgnore(['sequence_date' => $day, 'last_number' => 0]);

        $current = DB::table('order_sequences')->where('sequence_date', $day)->lockForUpdate()->value('last_number');
        $next = (int) $current + 1;

        DB::table('order_sequences')->where('sequence_date', $day)->update(['last_number' => $next]);

        return sprintf('%s-%s-%04d', config('store.order_prefix'), $date->format('Ymd'), $next);
    }
}

<?php

namespace App\Http\Controllers\Pos;

use App\Models\Shift;
use App\Support\BusinessRule;
use Illuminate\Http\Request;

/** Transaksi hanya bisa dibuat saat shift terbuka. */
trait RequiresOpenShift
{
    protected function openShift(Request $request): Shift
    {
        $shift = $request->user()->openShift;
        BusinessRule::check($shift !== null, 'Buka shift terlebih dahulu sebelum mencatat transaksi.', 'shift');

        return $shift;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Laporan tutup shift: kas awal, penjualan tunai, non-tunai, selisih kas. */
class ShiftReportController extends Controller
{
    public function index(Request $request, ReportService $reports): Response
    {
        $period = DashboardController::periodFrom($request);

        return Inertia::render('Admin/Shifts', [
            'period' => $period->toArray(),
            'shifts' => fn () => $reports->shifts($period),
        ]);
    }
}

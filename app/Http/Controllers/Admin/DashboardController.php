<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ReportService;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Dashboard & tren penjualan owner (semua periode). */
class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): Response
    {
        $period = $this->period($request);

        return Inertia::render('Admin/Dashboard', [
            'period' => $period->toArray(),
            'presets' => ReportPeriod::PRESETS,
            'comparison' => $reports->comparison($period),
            'daily' => $reports->daily($period),
            'channels' => $reports->byChannel($period),
            'paymentMethods' => $reports->byPaymentMethod($period),
            'categories' => $reports->byCategory($period),
            'topByQuantity' => $reports->topProducts($period, 'quantity'),
            'topByRevenue' => $reports->topProducts($period, 'revenue'),
            'hourly' => $reports->hourly($period),
            'cashiers' => $reports->byCashier($period),
            'statusCounts' => $reports->statusCounts($period),
            'lowStock' => Product::lowStock()->orderBy('stock')->get()
                ->map(fn (Product $p) => $p->only(['id', 'name', 'stock', 'stock_minimum'])),
        ]);
    }

    public static function periodFrom(Request $request): ReportPeriod
    {
        $data = $request->validate([
            'period' => ['nullable', 'in:'.implode(',', ReportPeriod::PRESETS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return ReportPeriod::make($data['period'] ?? 'today', $data['from'] ?? null, $data['to'] ?? null);
    }

    private function period(Request $request): ReportPeriod
    {
        return self::periodFrom($request);
    }
}

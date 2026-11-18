<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\ReportService;
use App\Support\Lazy;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Dashboard ringkas kasir: hari ini & 7 hari terakhir. */
class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): Response
    {
        $preset = $request->query('period') === '7d' ? '7d' : 'today';
        $period = ReportPeriod::make($preset);
        $canViewRevenue = Lazy::memo(fn () => $request->user()->isOwner() || StoreSetting::current()->cashier_can_view_revenue);
        // Tanpa izin lihat omzet, kolom revenue dibuang dari setiap baris
        $visible = fn (array $rows) => $canViewRevenue() ? $rows : array_map(fn ($row) => array_diff_key($row, ['revenue' => 0]), $rows);

        // Props lazy: partial reload layout (prop `pos`) tidak ikut menjalankan query laporan
        return Inertia::render('Pos/Dashboard', [
            'period' => $period->toArray(),
            'summary' => function () use ($reports, $period, $canViewRevenue) {
                $summary = $reports->summary($period);

                return $canViewRevenue() ? $summary : ['transactions' => $summary['transactions']];
            },
            'channels' => fn () => $visible($reports->byChannel($period)),
            'topProducts' => fn () => $visible($reports->topProducts($period)),
            'daily' => fn () => $preset === '7d' ? $visible($reports->daily($period)) : [],
            'statusCounts' => fn () => $reports->statusCounts($period),
            'lowStock' => fn () => Product::lowStock()->orderBy('stock')->limit(10)->get()
                ->map(fn (Product $p) => $p->only(['id', 'name', 'stock', 'stock_minimum'])),
        ]);
    }
}

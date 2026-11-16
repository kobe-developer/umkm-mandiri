<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\ReportService;
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
        $canViewRevenue = $request->user()->isOwner() || StoreSetting::current()->cashier_can_view_revenue;

        $summary = $reports->summary($period);
        $channels = $reports->byChannel($period);
        $top = $reports->topProducts($period);
        $daily = $preset === '7d' ? $reports->daily($period) : [];

        if (! $canViewRevenue) {
            $summary = ['transactions' => $summary['transactions']];
            $channels = array_map(fn ($c) => array_diff_key($c, ['revenue' => 0]), $channels);
            $top = array_map(fn ($p) => array_diff_key($p, ['revenue' => 0]), $top);
            $daily = array_map(fn ($d) => array_diff_key($d, ['revenue' => 0]), $daily);
        }

        return Inertia::render('Pos/Dashboard', [
            'period' => $period->toArray(),
            'summary' => $summary,
            'channels' => $channels,
            'topProducts' => $top,
            'daily' => $daily,
            'statusCounts' => $reports->statusCounts($period),
            'lowStock' => Product::lowStock()->orderBy('stock')->limit(10)->get()
                ->map(fn (Product $p) => $p->only(['id', 'name', 'stock', 'stock_minimum'])),
        ]);
    }
}

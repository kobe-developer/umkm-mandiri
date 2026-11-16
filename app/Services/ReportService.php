<?php

namespace App\Services;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Support\ReportPeriod;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Agregat penjualan. Omzet = order Selesai + Lunas (sesuai PRD), diberi tanggal
 * berdasarkan waktu selesai (completed_at). Jam ramai memakai waktu order dibuat.
 * Omzet per kategori/produk dihitung dari subtotal item (sebelum diskon order).
 */
class ReportService
{
    private const TOP_PRODUCTS_LIMIT = 10;

    /** @return array{revenue: int, transactions: int, average: int} */
    public function summary(ReportPeriod $period): array
    {
        $row = $this->revenueOrders($period)->selectRaw('COALESCE(SUM(total), 0) as revenue, COUNT(*) as transactions')->first();
        $revenue = (int) $row->revenue;
        $count = (int) $row->transactions;

        return ['revenue' => $revenue, 'transactions' => $count, 'average' => $count > 0 ? intdiv($revenue, $count) : 0];
    }

    /** @return array<string, mixed> */
    public function comparison(ReportPeriod $period): array
    {
        $current = $this->summary($period);
        $previous = $this->summary($period->previous());

        $change = [];
        foreach ($current as $key => $value) {
            $change[$key] = $previous[$key] > 0 ? round(($value - $previous[$key]) / $previous[$key] * 100, 1) : null;
        }

        return ['current' => $current, 'previous' => $previous, 'change' => $change];
    }

    /** @return list<array{date: string, revenue: int, transactions: int}> */
    public function daily(ReportPeriod $period): array
    {
        $rows = $this->revenueOrders($period)
            ->selectRaw('DATE(completed_at) as day, SUM(total) as revenue, COUNT(*) as transactions')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $result = [];
        foreach (CarbonPeriod::create($period->start, $period->end->startOfDay()) as $date) {
            $key = $date->toDateString();
            $result[] = [
                'date' => $key,
                'revenue' => (int) ($rows[$key]->revenue ?? 0),
                'transactions' => (int) ($rows[$key]->transactions ?? 0),
            ];
        }

        return $result;
    }

    /** @return list<array{key: string, label: string, revenue: int, transactions: int}> */
    public function byChannel(ReportPeriod $period): array
    {
        $rows = $this->revenueOrders($period)
            ->selectRaw('channel, SUM(total) as revenue, COUNT(*) as transactions')
            ->groupBy('channel')
            ->get()
            ->keyBy('channel');

        return array_map(fn (OrderChannel $c) => [
            'key' => $c->value,
            'label' => $c->label(),
            'revenue' => (int) ($rows[$c->value]->revenue ?? 0),
            'transactions' => (int) ($rows[$c->value]->transactions ?? 0),
        ], OrderChannel::cases());
    }

    /** @return list<array{label: string, revenue: int, transactions: int}> */
    public function byPaymentMethod(ReportPeriod $period): array
    {
        return $this->revenueOrders($period, 'orders')
            ->join('payment_methods', 'payment_methods.id', '=', 'orders.payment_method_id')
            ->selectRaw('payment_methods.name as label, SUM(orders.total) as revenue, COUNT(*) as transactions')
            ->groupBy('payment_methods.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'revenue' => (int) $r->revenue, 'transactions' => (int) $r->transactions])
            ->all();
    }

    /** @return list<array{label: string, revenue: int, quantity: int}> */
    public function byCategory(ReportPeriod $period): array
    {
        return $this->revenueItems($period)
            ->selectRaw("COALESCE(order_items.category_name, 'Lainnya') as label, SUM(order_items.subtotal) as revenue, SUM(order_items.quantity) as quantity")
            ->groupBy('label')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'revenue' => (int) $r->revenue, 'quantity' => (int) $r->quantity])
            ->all();
    }

    /** @return list<array{name: string, quantity: int, revenue: int}> */
    public function topProducts(ReportPeriod $period, string $orderBy = 'quantity'): array
    {
        return $this->revenueItems($period)
            ->selectRaw('order_items.product_name as name, SUM(order_items.quantity) as quantity, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc($orderBy === 'revenue' ? 'revenue' : 'quantity')
            ->limit(self::TOP_PRODUCTS_LIMIT)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'quantity' => (int) $r->quantity, 'revenue' => (int) $r->revenue])
            ->all();
    }

    /** @return list<array{hour: int, revenue: int, transactions: int}> */
    public function hourly(ReportPeriod $period): array
    {
        $rows = $this->revenueOrders($period)
            ->selectRaw('HOUR(created_at) as hour, SUM(total) as revenue, COUNT(*) as transactions')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        return array_map(fn (int $h) => [
            'hour' => $h,
            'revenue' => (int) ($rows[$h]->revenue ?? 0),
            'transactions' => (int) ($rows[$h]->transactions ?? 0),
        ], range(0, 23));
    }

    /** @return list<array{name: string, revenue: int, transactions: int}> */
    public function byCashier(ReportPeriod $period): array
    {
        return $this->revenueOrders($period, 'orders')
            ->join('users', 'users.id', '=', 'orders.cashier_id')
            ->selectRaw('users.name as name, SUM(orders.total) as revenue, COUNT(*) as transactions')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'revenue' => (int) $r->revenue, 'transactions' => (int) $r->transactions])
            ->all();
    }

    /** Ringkasan tutup shift dalam periode: kas awal, tunai, non-tunai, selisih. */
    public function shifts(ReportPeriod $period): array
    {
        $sales = DB::table('orders')
            ->join('payment_methods', 'payment_methods.id', '=', 'orders.payment_method_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereNotNull('orders.shift_id')
            ->selectRaw('orders.shift_id, SUM(CASE WHEN payment_methods.type = ? THEN orders.total ELSE 0 END) as cash, SUM(CASE WHEN payment_methods.type <> ? THEN orders.total ELSE 0 END) as non_cash', [PaymentMethodType::Cash->value, PaymentMethodType::Cash->value])
            ->groupBy('orders.shift_id');

        return DB::table('shifts')
            ->join('users', 'users.id', '=', 'shifts.user_id')
            ->leftJoinSub($sales, 'sales', 'sales.shift_id', '=', 'shifts.id')
            ->whereBetween('shifts.opened_at', $period->range())
            ->orderByDesc('shifts.opened_at')
            ->get(['shifts.*', 'users.name as cashier_name', 'sales.cash', 'sales.non_cash'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'cashier_name' => $s->cashier_name,
                'opened_at' => $s->opened_at,
                'closed_at' => $s->closed_at,
                'opening_cash' => (int) $s->opening_cash,
                'cash_sales' => (int) $s->cash,
                'non_cash_sales' => (int) $s->non_cash,
                'closing_cash_system' => $s->closing_cash_system !== null ? (int) $s->closing_cash_system : null,
                'closing_cash_actual' => $s->closing_cash_actual !== null ? (int) $s->closing_cash_actual : null,
                'cash_difference' => $s->cash_difference !== null ? (int) $s->cash_difference : null,
            ])
            ->all();
    }

    /** Jumlah order per status (semua kanal) untuk periode, termasuk yang belum selesai. */
    public function statusCounts(ReportPeriod $period): array
    {
        return Order::whereBetween('created_at', $period->range())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    private function revenueOrders(ReportPeriod $period, string $table = 'orders'): Builder
    {
        return DB::table('orders')
            ->where("{$table}.status", OrderStatus::Completed->value)
            ->where("{$table}.payment_status", PaymentStatus::Paid->value)
            ->whereBetween("{$table}.completed_at", $period->range());
    }

    private function revenueItems(ReportPeriod $period): Builder
    {
        return $this->revenueOrders($period)->join('order_items', 'order_items.order_id', '=', 'orders.id');
    }
}

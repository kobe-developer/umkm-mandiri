<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exports\TransactionsExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\ReportPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Daftar transaksi dengan filter & detail; export Excel/PDF. */
class TransactionController extends Controller
{
    private const PER_PAGE = 25;

    private const PDF_ROW_LIMIT = 2000;

    public function index(Request $request): Response
    {
        [$period, $filters] = $this->filters($request);

        return Inertia::render('Admin/Transactions', [
            'orders' => fn () => OrderResource::collection(
                $this->query($period, $filters)->with(['paymentMethod', 'cashier'])->withCount('items')
                    ->latest()->paginate(self::PER_PAGE)->withQueryString()
            ),
            'period' => $period->toArray(),
            'filters' => $filters,
            'options' => fn () => [
                'channels' => array_map(fn (OrderChannel $c) => ['value' => $c->value, 'label' => $c->label()], OrderChannel::cases()),
                'statuses' => OrderStatus::options(),
                'paymentStatuses' => array_map(fn (PaymentStatus $s) => ['value' => $s->value, 'label' => $s->label()], PaymentStatus::cases()),
                'paymentMethods' => PaymentMethod::orderBy('name')->get(['id', 'name'])->map->only(['id', 'name']),
                'cashiers' => User::orderBy('name')->get(['id', 'name'])->map->only(['id', 'name']),
            ],
        ]);
    }

    public function export(Request $request, string $format): HttpResponse
    {
        [$period, $filters] = $this->filters($request);
        $query = $this->query($period, $filters)->with(['paymentMethod', 'cashier', 'items'])->oldest();
        $name = 'transaksi_'.$period->start->format('Ymd').'-'.$period->end->format('Ymd');

        if ($format === 'xlsx') {
            return Excel::download(new TransactionsExport($query), "{$name}.xlsx");
        }

        return Pdf::loadView('exports.transactions', [
            'orders' => $query->limit(self::PDF_ROW_LIMIT)->get(),
            'summary' => $this->summary($this->query($period, $filters)),
            'period' => $period,
            'store' => StoreSetting::current(),
            'limit' => self::PDF_ROW_LIMIT,
        ])->setPaper('a4', 'landscape')->download("{$name}.pdf");
    }

    /** Ringkasan omzet (Selesai + Lunas) dari transaksi yang sama dengan isi tabel. */
    private function summary(Builder $query): array
    {
        $row = $query->revenue()->toBase()->selectRaw('COALESCE(SUM(total), 0) as revenue, COUNT(*) as transactions')->first();
        $count = (int) $row->transactions;

        return ['revenue' => (int) $row->revenue, 'transactions' => $count, 'average' => $count > 0 ? intdiv((int) $row->revenue, $count) : 0];
    }

    /** @return array{0: ReportPeriod, 1: array<string, mixed>} */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'channel' => ['nullable', Rule::enum(OrderChannel::class)],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'payment_method_id' => ['nullable', 'integer'],
            'cashier_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:50'],
        ]);

        return [DashboardController::periodFrom($request), array_filter($data, fn ($v) => $v !== null && $v !== '')];
    }

    private function query(ReportPeriod $period, array $filters): Builder
    {
        return Order::query()
            ->whereBetween('created_at', $period->range())
            ->when($filters['channel'] ?? null, fn (Builder $q, $v) => $q->where('channel', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['payment_status'] ?? null, fn (Builder $q, $v) => $q->where('payment_status', $v))
            ->when($filters['payment_method_id'] ?? null, fn (Builder $q, $v) => $q->where('payment_method_id', $v))
            ->when($filters['cashier_id'] ?? null, fn (Builder $q, $v) => $q->where('cashier_id', $v))
            ->when($filters['search'] ?? null, fn (Builder $q, string $s) => $q->where(fn (Builder $q) => $q
                ->where('order_number', 'like', "%{$s}%")
                ->orWhere('customer_name', 'like', "%{$s}%")
                ->orWhere('whatsapp_number', 'like', "%{$s}%")));
    }
}

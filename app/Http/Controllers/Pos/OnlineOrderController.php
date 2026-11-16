<?php

namespace App\Http\Controllers\Pos;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Daftar order online per status: Baru, Dikonfirmasi, Diproses, Siap, Selesai, Dibatalkan. */
class OnlineOrderController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): Response
    {
        $data = $request->validate(['status' => ['nullable', Rule::enum(OrderStatus::class)]]);
        $status = $data['status'] ?? OrderStatus::New->value;

        $base = Order::where('channel', OrderChannel::Online->value);

        // Status aktif ditampilkan semua; status final (Selesai/Dibatalkan) hanya sejak kemarin
        $counts = (clone $base)->where(fn ($q) => $this->scopeWindow($q))
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $orders = (clone $base)->where('status', $status)
            ->where(fn ($q) => $this->scopeWindow($q))
            ->with(['items', 'plannedPaymentMethod', 'paymentMethod'])
            ->when(in_array($status, [OrderStatus::New->value, OrderStatus::Confirmed->value], true), fn ($q) => $q->oldest(), fn ($q) => $q->latest())
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Pos/Online', [
            'orders' => OrderResource::collection($orders),
            'status' => $status,
            'statuses' => OrderStatus::options(),
            'counts' => $counts,
        ]);
    }

    private function scopeWindow(Builder $query): Builder
    {
        $final = [OrderStatus::Completed->value, OrderStatus::Cancelled->value];

        return $query->whereNotIn('status', $final)
            ->orWhere(fn (Builder $q) => $q->whereIn('status', $final)->where('created_at', '>=', today()->subDay()));
    }
}

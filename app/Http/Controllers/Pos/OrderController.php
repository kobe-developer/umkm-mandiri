<?php

namespace App\Http\Controllers\Pos;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\WhatsAppLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private const PER_PAGE = 20;

    /** Riwayat transaksi. Default: shift berjalan. Filter "unpaid" = daftar Belum dibayar. */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'scope' => ['nullable', 'in:shift,today,unpaid,active'],
            'search' => ['nullable', 'string', 'max:50'],
        ]);
        $scope = $filters['scope'] ?? 'shift';
        $shift = $request->user()->openShift;

        $orders = Order::query()
            ->with('paymentMethod')
            ->withCount('items')
            ->when($scope === 'shift', fn (Builder $q) => $shift
                ? $q->where(fn (Builder $q) => $q->where('shift_id', $shift->id)->orWhere('created_at', '>=', $shift->opened_at))
                : $q->whereDate('created_at', today()))
            ->when($scope === 'today', fn (Builder $q) => $q->whereDate('created_at', today()))
            ->when($scope === 'unpaid', fn (Builder $q) => $q->unpaid()->where('status', '!=', OrderStatus::New->value))
            ->when($scope === 'active', fn (Builder $q) => $q->active())
            ->when($filters['search'] ?? null, fn (Builder $q, string $s) => $q->where(fn (Builder $q) => $q
                ->where('order_number', 'like', "%{$s}%")
                ->orWhere('customer_name', 'like', "%{$s}%")
                ->orWhere('table_number', $s)))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Pos/Orders/Index', [
            'orders' => OrderResource::collection($orders),
            'filters' => ['scope' => $scope, 'search' => $filters['search'] ?? ''],
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $order->load(['items', 'paymentMethod', 'plannedPaymentMethod', 'cashier']);
        $settings = StoreSetting::current();
        $statusUrl = $order->public_token ? route('menu.order.show', ['order' => $order->order_number, 't' => $order->public_token]) : null;

        return Inertia::render('Pos/Orders/Show', [
            'order' => (new OrderResource($order))->resolve($request),
            'statusUrl' => $statusUrl,
            'whatsappLink' => WhatsAppLink::forOrderConfirmation($order, $settings->store_name, $statusUrl),
            'paymentMethods' => PaymentMethod::forChannel($order->channel)->get()
                ->map(fn (PaymentMethod $m) => ['id' => $m->id, 'name' => $m->name, 'type' => $m->type->value, 'qris_image_url' => $m->qris_image_url]),
            'products' => $order->isOnline() && $order->status === OrderStatus::New
                ? Product::online()->orderBy('name')->get(['id', 'name', 'price', 'is_available', 'track_stock', 'stock'])
                    ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->price, 'is_sold_out' => $p->is_sold_out])
                : [],
            'isOnline' => $order->channel === OrderChannel::Online,
        ]);
    }

    /** Struk yang bisa dicetak dari browser. */
    public function receipt(Request $request, Order $order): Response
    {
        $order->load(['items', 'paymentMethod', 'cashier']);
        $settings = StoreSetting::current();

        return Inertia::render('Pos/Orders/Receipt', [
            'order' => (new OrderResource($order))->resolve($request),
            'store' => $settings->only(['store_name', 'address', 'whatsapp_number', 'receipt_note']),
        ]);
    }
}

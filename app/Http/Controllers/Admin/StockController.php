<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\ActivityLogger;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Stok per produk: stok masuk, penyesuaian (alasan wajib), riwayat. */
class StockController extends Controller
{
    private const PER_PAGE = 30;

    public function index(): Response
    {
        return Inertia::render('Admin/Stock/Index', [
            'products' => Product::with('category:id,name')->where('track_stock', true)
                ->orderBy('category_id')->orderBy('name')->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'category' => $p->category?->name,
                    'stock' => $p->stock,
                    'stock_minimum' => $p->stock_minimum,
                    'is_low_stock' => $p->is_low_stock,
                    'is_active' => $p->is_active,
                ]),
            'untrackedCount' => Product::where('track_stock', false)->count(),
        ]);
    }

    public function movements(Request $request): Response
    {
        $filters = $request->validate([
            'product_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
        ]);

        return Inertia::render('Admin/Stock/Movements', [
            'movements' => StockMovement::with(['product:id,name', 'user:id,name', 'order:id,order_number'])
                ->when($filters['product_id'] ?? null, fn ($q, $v) => $q->where('product_id', $v))
                ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
                ->latest('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString()
                ->through(fn (StockMovement $m) => [
                    'id' => $m->id,
                    'created_at' => $m->created_at,
                    'product' => $m->product?->name,
                    'type' => $m->type->value,
                    'type_label' => $m->type->label(),
                    'quantity' => $m->quantity,
                    'stock_after' => $m->stock_after,
                    'reason' => $m->reason,
                    'user' => $m->user?->name,
                    'order_number' => $m->order?->order_number,
                ]),
            'products' => Product::where('track_stock', true)->orderBy('name')->get(['id', 'name'])->map->only(['id', 'name']),
            'types' => array_map(fn (StockMovementType $t) => ['value' => $t->value, 'label' => $t->label()], StockMovementType::cases()),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request, Product $product, StockService $stock, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([StockMovementType::In->value, StockMovementType::Adjustment->value])],
            'quantity' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'reason' => [Rule::requiredIf($request->input('type') === StockMovementType::Adjustment->value), 'nullable', 'string', 'max:255'],
        ], ['reason.required' => 'Alasan penyesuaian wajib diisi (koreksi/rusak/basi).', 'quantity.not_in' => 'Jumlah tidak boleh 0.']);

        $type = StockMovementType::from($data['type']);
        $quantity = $type === StockMovementType::In ? abs($data['quantity']) : (int) $data['quantity'];

        $movement = DB::transaction(fn () => $stock->adjust($product, $type, $quantity, $data['reason'] ?? null, $request->user()));
        $logger->log('stock_'.$type->value, $product, null, ['quantity' => $quantity, 'stock_after' => $movement->stock_after], $data['reason'] ?? $type->label());

        return back()->with('success', "Stok {$product->name} sekarang {$movement->stock_after}.");
    }
}

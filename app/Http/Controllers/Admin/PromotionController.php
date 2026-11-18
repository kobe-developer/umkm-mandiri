<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionRequest;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Back-office promo produk (owner). */
class PromotionController extends Controller
{
    private const PER_PAGE = 20;

    private const STATUSES = [Promotion::STATUS_ACTIVE, Promotion::STATUS_SCHEDULED, Promotion::STATUS_EXPIRED];

    public function __construct(private readonly PromotionService $promotions) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['semua', ...self::STATUSES])],
        ]);
        $status = $filters['status'] ?? Promotion::STATUS_ACTIVE;

        return Inertia::render('Admin/Promotions', [
            'promotions' => fn () => Promotion::with('product:id,name,price,deleted_at')
                ->withStatus($status)
                ->orderByDesc('active_until')->orderByDesc('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString()
                ->through(fn (Promotion $p) => [
                    'id' => $p->id,
                    'product_id' => $p->product_id,
                    'product_name' => $p->product?->name,
                    'product_deleted' => $p->product?->trashed() ?? true,
                    'normal_price' => $p->product?->price,
                    'price' => $p->price,
                    'channels' => $p->channels,
                    'active_from' => $p->active_from->toDateString(),
                    'active_until' => $p->active_until->toDateString(),
                    'description' => $p->description,
                    'status' => $p->status(),
                ]),
            'products' => fn () => Product::orderBy('name')->get(['id', 'name', 'price'])
                ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->price]),
            'channels' => array_map(fn (OrderChannel $c) => ['value' => $c->value, 'label' => $c->label()], OrderChannel::cases()),
            'filters' => ['status' => $status],
            'today' => today()->toDateString(),
        ]);
    }

    public function store(PromotionRequest $request): RedirectResponse
    {
        $promotion = $this->promotions->create($request->validated());

        return back()->with('success', "Promo {$promotion->product->name} disimpan.");
    }

    public function update(PromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $this->promotions->update($promotion, $request->validated());

        return back()->with('success', "Promo {$promotion->product->name} diperbarui.");
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $name = $promotion->product->name;
        $this->promotions->delete($promotion);

        return back()->with('success', "Promo {$name} dihapus.");
    }
}

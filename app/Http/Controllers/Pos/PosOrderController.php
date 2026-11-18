<?php

namespace App\Http\Controllers\Pos;

use App\Enums\OrderChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePosOrderRequest;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\OrderCreationService;
use App\Services\PromotionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Layar kasir untuk dine in & take away. */
class PosOrderController extends Controller
{
    use RequiresOpenShift;

    public function create(Request $request, PromotionCatalog $catalog): Response|RedirectResponse
    {
        if (! $request->user()->openShift) {
            return redirect()->route('pos.shift.show')->with('error', 'Buka shift terlebih dahulu.');
        }

        // Props lazy: partial reload layout (prop `pos`) tidak ikut memuat katalog
        return Inertia::render('Pos/Cashier', [
            'categories' => fn () => Category::where('is_active', true)->ordered()->get(['id', 'name']),
            // Harga promo per kanal dipilih di browser hanya untuk tampilan; server menghitung ulang.
            'products' => function () use ($catalog) {
                $products = Product::active()->orderBy('sort_order')->orderBy('name')->get();
                $promotions = $catalog->activeFor($products->modelKeys());

                return $products->map(fn (Product $p) => [
                    'id' => $p->id,
                    'category_id' => $p->category_id,
                    'name' => $p->name,
                    'price' => $p->price,
                    'promo' => $catalog->present($p, $promotions->get($p->id)),
                    'photo_url' => $p->photo_url,
                    'is_sold_out' => $p->is_sold_out,
                    'track_stock' => $p->track_stock,
                    'stock' => $p->stock,
                ]);
            },
            'paymentMethods' => fn () => PaymentMethod::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (PaymentMethod $m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'type' => $m->type->value,
                    'channels' => $m->channels,
                    'qris_image_url' => $m->qris_image_url,
                ]),
            'channels' => [
                ['value' => OrderChannel::DineIn->value, 'label' => OrderChannel::DineIn->label()],
                ['value' => OrderChannel::TakeAway->value, 'label' => OrderChannel::TakeAway->label()],
            ],
        ]);
    }

    public function store(StorePosOrderRequest $request, OrderCreationService $orders): RedirectResponse
    {
        $order = $orders->createPosOrder($request->validated(), $request->user(), $this->openShift($request));

        $message = $order->isPaid()
            ? "Order {$order->order_number} tersimpan & lunas."
            : "Order {$order->order_number} tersimpan (belum dibayar).";

        return redirect()->route('pos.orders.show', $order)->with('success', $message);
    }
}

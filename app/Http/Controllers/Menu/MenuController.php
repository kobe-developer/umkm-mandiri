<?php

namespace App\Http\Controllers\Menu;

use App\Enums\OrderChannel;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\OrderTracker;
use App\Services\PromotionCatalog;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(OrderTracker $tracker, PromotionCatalog $catalog): Response
    {
        $settings = StoreSetting::current();

        $online = Product::online()->orderBy('sort_order')->orderBy('name')->get();
        $promotions = $catalog->activeFor($online->modelKeys());

        // price = harga yang dibayar hari ini; normal_price terisi hanya bila sedang promo (untuk harga coret)
        $products = $online->map(function (Product $p) use ($catalog, $promotions) {
            $priced = $catalog->priceFor($p, $promotions->get($p->id), OrderChannel::Online);
            $promo = $priced['promotion_id'] ? $promotions->get($p->id) : null;

            return [
                'id' => $p->id,
                'category_id' => $p->category_id,
                'name' => $p->name,
                'price' => $priced['unit_price'],
                'normal_price' => $promo ? $p->price : null,
                'promo_description' => $promo?->description,
                'promo_until' => $promo?->active_until->toDateString(),
                'description' => $p->description,
                'photo_url' => $p->photo_url,
                'is_sold_out' => $p->is_sold_out,
            ];
        });

        return Inertia::render('Menu/Index', [
            'categories' => Category::where('is_active', true)->ordered()
                ->whereIn('id', $products->pluck('category_id')->unique())
                ->get(['id', 'name']),
            'products' => $products->values(),
            'paymentMethods' => PaymentMethod::forChannel(OrderChannel::Online)->get()
                ->map(fn (PaymentMethod $m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'type' => $m->type->value,
                    'bank_name' => $m->bank_name,
                    'account_number' => $m->account_number,
                    'account_holder' => $m->account_holder,
                    'qris_image_url' => $m->qris_image_url,
                ]),
            'trackedOrders' => fn () => $tracker->active(),
            'hasOrderHistory' => fn () => $tracker->hasHistory(),
            'storeSettings' => [
                'name' => $settings->store_name,
                'address' => $settings->address,
                'is_open' => $settings->is_open,
                'online_order_enabled' => $settings->online_order_enabled,
                'accepts_orders' => $settings->acceptsOnlineOrders(),
                'delivery_note' => $settings->delivery_note,
                'min_order' => $settings->min_order,
                'allow_pickup' => $settings->allow_pickup,
                'allow_delivery' => $settings->allow_delivery,
                'operating_hours' => $settings->operating_hours,
            ],
        ]);
    }
}

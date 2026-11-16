<?php

namespace App\Http\Controllers\Menu;

use App\Enums\OrderChannel;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        $settings = StoreSetting::current();

        $products = Product::online()->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'category_id' => $p->category_id,
                'name' => $p->name,
                'price' => $p->price,
                'description' => $p->description,
                'photo_url' => $p->photo_url,
                'is_sold_out' => $p->is_sold_out,
            ]);

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

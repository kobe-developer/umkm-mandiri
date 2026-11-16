<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Halaman status order publik lewat link unik (nomor order + token). */
class OrderStatusController extends Controller
{
    public function show(Request $request, Order $order): Response
    {
        $token = (string) $request->query('t', '');
        abort_unless($order->public_token && hash_equals($order->public_token, $token), 404);

        $order->load(['items', 'plannedPaymentMethod', 'paymentMethod']);
        $settings = StoreSetting::current();

        return Inertia::render('Menu/OrderStatus', [
            'order' => (new OrderResource($order))->resolve($request),
            'isNew' => $request->boolean('baru'),
            'storeWhatsapp' => $settings->whatsapp_number,
            'paymentMethod' => $order->plannedPaymentMethod?->only(['name', 'type', 'bank_name', 'account_number', 'account_holder', 'qris_image_url']),
        ]);
    }
}

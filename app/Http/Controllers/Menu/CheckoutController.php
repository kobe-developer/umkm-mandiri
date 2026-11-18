<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\OrderCreationService;
use App\Services\OrderTracker;
use App\Services\PushSubscriptionService;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, OrderCreationService $orders, OrderTracker $tracker, PushSubscriptionService $push): RedirectResponse
    {
        $order = $orders->createOnlineOrder($request->validated(), $request->ip());
        $tracker->remember($order);

        // Langganan notifikasi bersifat opsional: kegagalannya tidak boleh menggagalkan pesanan
        if ($subscription = $request->validated('push_subscription')) {
            rescue(fn () => $push->subscribeOrder($order, $subscription));
        }

        return redirect()
            ->route('menu.order.show', ['order' => $order->order_number, 't' => $order->public_token, 'baru' => 1]);
    }
}

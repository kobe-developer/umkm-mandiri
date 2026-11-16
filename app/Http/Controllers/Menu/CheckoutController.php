<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\OrderCreationService;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, OrderCreationService $orders): RedirectResponse
    {
        $order = $orders->createOnlineOrder($request->validated(), $request->ip());

        return redirect()
            ->route('menu.order.show', ['order' => $order->order_number, 't' => $order->public_token, 'baru' => 1]);
    }
}

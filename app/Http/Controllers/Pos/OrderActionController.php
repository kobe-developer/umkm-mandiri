<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderItemsRequest;
use App\Models\Order;
use App\Services\OrderLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderActionController extends Controller
{
    use RequiresOpenShift;

    public function __construct(private readonly OrderLifecycleService $lifecycle) {}

    public function pay(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'amount_received' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ]);

        $order = $this->lifecycle->pay($order, $data['payment_method_id'], $data['amount_received'] ?? null, $request->user(), $this->openShift($request));

        return back()->with('success', "Order {$order->order_number} lunas.");
    }

    public function advance(Request $request, Order $order): RedirectResponse
    {
        $order = $this->lifecycle->advance($order, $request->user());

        return back()->with('success', "Status order {$order->order_number}: {$order->status->label()}.");
    }

    public function complete(Request $request, Order $order): RedirectResponse
    {
        $order = $this->lifecycle->complete($order, $request->user());

        return back()->with('success', "Order {$order->order_number} selesai.");
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']], ['reason.required' => 'Alasan pembatalan wajib diisi.']);
        $order = $this->lifecycle->cancel($order, $data['reason'], $request->user());

        return back()->with('success', "Order {$order->order_number} dibatalkan.");
    }

    public function void(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:255'],
            'pin' => [$request->user()->isOwner() ? 'nullable' : 'required', 'string', 'max:6'],
        ], ['reason.required' => 'Alasan void wajib diisi.', 'pin.required' => 'PIN owner wajib diisi.']);

        $order = $this->lifecycle->void($order, $data['reason'], $request->user(), $data['pin'] ?? null);

        return back()->with('success', "Order {$order->order_number} di-void.");
    }

    public function updateItems(UpdateOrderItemsRequest $request, Order $order): RedirectResponse
    {
        $this->lifecycle->updateItems($order, $request->validated('items'), $request->user());

        return back()->with('success', 'Item order diperbarui.');
    }
}

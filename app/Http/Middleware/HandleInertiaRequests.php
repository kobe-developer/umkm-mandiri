<?php

namespace App\Http\Middleware;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role->value,
                    'is_owner' => $user->isOwner(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'store' => fn () => $this->storeInfo($user !== null),
            'pos' => fn () => $user ? $this->posInfo($request) : null,
            'config' => [
                'pollInterval' => config('store.poll_interval_seconds'),
                'realtime' => config('broadcasting.default') === 'reverb',
            ],
        ];
    }

    private function storeInfo(bool $authenticated): array
    {
        $settings = StoreSetting::current();

        return [
            'name' => $settings->store_name,
            'is_open' => $settings->is_open,
            'whatsapp_number' => $authenticated ? $settings->whatsapp_number : null,
        ];
    }

    private function posInfo(Request $request): array
    {
        $user = $request->user();
        $settings = StoreSetting::current();
        $shift = $user->openShift;

        return [
            'pending_online_count' => Order::where('channel', OrderChannel::Online->value)
                ->where('status', OrderStatus::New->value)->count(),
            'open_shift' => $shift ? ['id' => $shift->id, 'opened_at' => $shift->opened_at, 'opening_cash' => $shift->opening_cash] : null,
            'can_view_revenue' => $user->isOwner() || $settings->cashier_can_view_revenue,
            'can_discount' => $user->isOwner() || $settings->cashier_can_discount,
        ];
    }
}

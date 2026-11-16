<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Services\OrderTracker;
use Inertia\Inertia;
use Inertia\Response;

/** Halaman "Pesanan saya": order yang sedang berjalan + riwayat pembelian di perangkat ini. */
class OrderHistoryController extends Controller
{
    public function index(OrderTracker $tracker): Response
    {
        return Inertia::render('Menu/OrderHistory', [
            'orders' => fn () => $tracker->history(),
            'storeName' => StoreSetting::current()->store_name,
        ]);
    }
}

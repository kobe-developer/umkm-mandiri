<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

/** Setiap halaman GET merender komponen Inertia yang filenya benar-benar ada. */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    public function test_every_page_renders_its_component(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->setUpShop();
        $owner = $this->owner();
        $shift = $this->openShiftFor($owner);
        $product = Product::where('is_active', true)->first();
        $posOrder = $this->placePosOrder($owner, $shift, $product);
        $online = $this->placeOnlineOrder($product);

        $pages = [
            '/menu' => 'Menu/Index',
            "/menu/order/{$online->order_number}?t={$online->public_token}" => 'Menu/OrderStatus',
            '/pos' => 'Pos/Dashboard',
            '/pos?period=7d' => 'Pos/Dashboard',
            '/pos/shift' => 'Pos/Shift',
            '/pos/kasir' => 'Pos/Cashier',
            '/pos/orders' => 'Pos/Orders/Index',
            '/pos/orders?scope=unpaid' => 'Pos/Orders/Index',
            "/pos/orders/{$posOrder->order_number}" => 'Pos/Orders/Show',
            "/pos/orders/{$online->order_number}" => 'Pos/Orders/Show',
            "/pos/orders/{$posOrder->order_number}/struk" => 'Pos/Orders/Receipt',
            '/pos/online' => 'Pos/Online',
            '/pos/online?status=selesai' => 'Pos/Online',
            '/pos/produk' => 'Pos/Products',
            '/akun' => 'Account/Edit',
            '/admin' => 'Admin/Dashboard',
            '/admin?period=custom&from=2026-01-01&to=2026-12-31' => 'Admin/Dashboard',
            '/admin/transaksi?period=30d&channel=dine_in' => 'Admin/Transactions',
            '/admin/shift?period=month' => 'Admin/Shifts',
            '/admin/produk' => 'Admin/Products/Index',
            '/admin/produk/create' => 'Admin/Products/Form',
            "/admin/produk/{$product->id}/edit" => 'Admin/Products/Form',
            '/admin/kategori' => 'Admin/Categories',
            '/admin/pembayaran' => 'Admin/PaymentMethods',
            '/admin/stok' => 'Admin/Stock/Index',
            '/admin/stok/riwayat' => 'Admin/Stock/Movements',
            '/admin/user' => 'Admin/Users',
            '/admin/toko' => 'Admin/Store',
            '/admin/log' => 'Admin/ActivityLogs',
        ];

        $this->actingAs($owner);
        foreach ($pages as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn ($page) => $page->component($component));
        }

        auth()->logout();
        $this->get('/login')->assertOk()->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_cashier_without_shift_is_sent_to_shift_page(): void
    {
        $this->setUpShop();

        $this->actingAs($this->cashier())->get('/pos/kasir')->assertRedirect('/pos/shift');
    }
}

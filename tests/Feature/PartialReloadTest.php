<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

/**
 * Layout back-office memuat ulang prop `pos` (badge order online) lewat partial reload
 * ke URL halaman yang sedang dibuka. Prop halaman harus lazy (closure) agar partial reload
 * itu tidak ikut menjalankan query laporan/daftar yang hasilnya dibuang.
 */
class PartialReloadTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    /** Halaman back-office dengan partial reload `pos` → komponen. Pos/Online sengaja reload penuh. */
    private const BACK_OFFICE_PAGES = [
        '/pos' => 'Pos/Dashboard',
        '/pos?period=7d' => 'Pos/Dashboard',
        '/pos/shift' => 'Pos/Shift',
        '/pos/kasir' => 'Pos/Cashier',
        '/pos/orders' => 'Pos/Orders/Index',
        '/pos/produk' => 'Pos/Products',
        '/admin' => 'Admin/Dashboard',
        '/admin?period=7d' => 'Admin/Dashboard',
        '/admin/transaksi' => 'Admin/Transactions',
        '/admin/shift' => 'Admin/Shifts',
        '/admin/produk' => 'Admin/Products/Index',
        '/admin/produk/create' => 'Admin/Products/Form',
        '/admin/promo' => 'Admin/Promotions',
        '/admin/kategori' => 'Admin/Categories',
        '/admin/pembayaran' => 'Admin/PaymentMethods',
        '/admin/stok' => 'Admin/Stock/Index',
        '/admin/stok/riwayat' => 'Admin/Stock/Movements',
        '/admin/user' => 'Admin/Users',
        '/admin/toko' => 'Admin/Store',
        '/admin/log' => 'Admin/ActivityLogs',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->setUpShop();
    }

    public function test_pos_partial_reload_does_not_run_page_queries(): void
    {
        $owner = $this->owner();
        $shift = $this->openShiftFor($owner);
        $product = Product::where('is_active', true)->first();
        $posOrder = $this->placePosOrder($owner, $shift, $product);
        $online = $this->placeOnlineOrder($product);
        $this->actingAs($owner);

        // Account/Edit tidak punya query halaman: jumlah query-nya = biaya dasar (auth, session, prop `pos`)
        $baseline = $this->countQueries(fn () => $this->partialReload('/akun', 'Account/Edit', 'pos'));

        foreach (self::BACK_OFFICE_PAGES as $url => $component) {
            $queries = $this->countQueries(fn () => $this->partialReload($url, $component, 'pos')
                ->assertOk()
                ->assertJsonPath('component', $component)
                ->assertJsonStructure(['props' => ['pos' => ['pending_online_count']]]));

            $this->assertSame($baseline, $queries, "Partial reload `pos` di {$url} menjalankan query halaman.");
        }

        // Halaman dengan route model binding: hanya boleh menambah 1 query (binding itu sendiri)
        $boundPages = [
            "/pos/orders/{$posOrder->order_number}" => 'Pos/Orders/Show',
            "/pos/orders/{$online->order_number}" => 'Pos/Orders/Show',
            "/admin/produk/{$product->id}/edit" => 'Admin/Products/Form',
        ];
        foreach ($boundPages as $url => $component) {
            $queries = $this->countQueries(fn () => $this->partialReload($url, $component, 'pos')->assertOk());

            $this->assertSame($baseline + 1, $queries, "Partial reload `pos` di {$url} menjalankan query halaman.");
        }
    }

    public function test_tracked_orders_reload_on_menu_does_not_reload_catalog(): void
    {
        $this->placeOnlineOrder(Product::where('is_active', true)->first());

        $queries = $this->captureQueries(fn () => $this->partialReload('/menu', 'Menu/Index', 'trackedOrders')
            ->assertOk()
            ->assertJsonStructure(['props' => ['trackedOrders']]));

        foreach (['products', 'categories', 'payment_methods', 'promotions'] as $table) {
            $this->assertEmpty(
                array_filter($queries, fn (string $sql) => preg_match("/from [`\"]?{$table}[`\"]?/i", $sql)),
                "Reload `trackedOrders` ikut memuat tabel {$table}.",
            );
        }
    }

    private function partialReload(string $url, string $component, string $only): TestResponse
    {
        return $this->get($url, [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $this->inertiaVersion(),
            'X-Inertia-Partial-Component' => $component,
            'X-Inertia-Partial-Data' => $only,
        ]);
    }

    /** Versi aset diset middleware Inertia saat request; kirim ulang agar tidak dibalas 409. */
    private function inertiaVersion(): string
    {
        return Inertia::getVersion();
    }

    private function countQueries(callable $request): int
    {
        return count($this->captureQueries($request));
    }

    /** @return list<string> */
    private function captureQueries(callable $request): array
    {
        $this->get('/menu'); // hangatkan cache (versi aset, setting toko) agar tidak terhitung
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        DB::disableQueryLog();

        return array_map(fn (array $query) => $query['query'], DB::getQueryLog());
    }
}

<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class BackOfficeTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        $this->owner = $this->owner();
        $this->actingAs($this->owner);
    }

    public function test_owner_creates_product_with_compressed_photo_and_initial_stock(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $this->post('/admin/produk', [
            'category_id' => $category->id,
            'name' => 'Nasi Goreng Spesial',
            'price' => 13000,
            'photo' => UploadedFile::fake()->image('produk.jpg', 2000, 1500),
            'track_stock' => true,
            'initial_stock' => 20,
            'stock_minimum' => 5,
            'show_online' => true,
        ])->assertRedirect('/admin/produk');

        $product = Product::sole();
        $this->assertStringEndsWith('.webp', $product->photo_path);
        Storage::disk('public')->assertExists($product->photo_path);
        [$width] = getimagesizefromstring(Storage::disk('public')->get($product->photo_path));
        $this->assertLessThanOrEqual(800, $width);
        $this->assertSame(20, $product->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'masuk', 'quantity' => 20]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'create', 'subject_type' => 'Product', 'user_id' => $this->owner->id]);
    }

    public function test_product_update_is_logged_with_before_and_after(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->put("/admin/produk/{$product->id}", [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'price' => 12000,
        ])->assertRedirect('/admin/produk');

        $this->assertDatabaseHas('activity_logs', ['action' => 'update', 'subject_id' => $product->id]);
        $log = ActivityLog::where('action', 'update')->sole();
        $this->assertSame(10000, $log->before['price']);
        $this->assertSame(12000, $log->after['price']);
    }

    public function test_deleting_product_keeps_it_for_history(): void
    {
        $product = Product::factory()->create();
        $this->delete("/admin/produk/{$product->id}")->assertRedirect();
        $this->assertSoftDeleted($product);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $used = Product::factory()->create()->category;
        $empty = Category::factory()->create();

        $this->delete("/admin/kategori/{$used->id}")->assertSessionHasErrors('category');
        $this->delete("/admin/kategori/{$empty->id}")->assertSessionHasNoErrors();
        $this->assertModelExists($used);
        $this->assertModelMissing($empty);
    }

    public function test_stock_adjustment_requires_reason_and_cannot_go_negative(): void
    {
        $product = Product::factory()->tracked(5)->create();

        $this->post("/admin/stok/{$product->id}", ['type' => 'penyesuaian', 'quantity' => -2])->assertSessionHasErrors('reason');
        $this->post("/admin/stok/{$product->id}", ['type' => 'penyesuaian', 'quantity' => -10, 'reason' => 'basi'])->assertSessionHasErrors('quantity');
        $this->post("/admin/stok/{$product->id}", ['type' => 'penyesuaian', 'quantity' => -2, 'reason' => 'rusak'])->assertSessionHasNoErrors();
        $this->post("/admin/stok/{$product->id}", ['type' => 'masuk', 'quantity' => 10])->assertSessionHasNoErrors();

        $this->assertSame(13, $product->fresh()->stock);
        $this->assertSame(2, $product->stockMovements()->count());
    }

    public function test_owner_cannot_deactivate_self_and_last_owner_is_protected(): void
    {
        $payload = ['name' => $this->owner->name, 'username' => $this->owner->username, 'role' => 'owner', 'is_active' => false];
        $this->put("/admin/user/{$this->owner->id}", $payload)->assertSessionHasErrors('is_active');
        $this->put("/admin/user/{$this->owner->id}", [...$payload, 'is_active' => true, 'role' => 'kasir'])->assertSessionHasErrors('is_active');
        $this->assertTrue($this->owner->fresh()->is_active);
    }

    public function test_owner_manages_cashier_accounts(): void
    {
        $this->post('/admin/user', ['name' => 'Kasir Dua', 'username' => 'kasir2', 'role' => 'kasir', 'password' => 'rahasia123'])->assertSessionHasNoErrors();
        $cashier = User::where('username', 'kasir2')->sole();

        $this->put("/admin/user/{$cashier->id}", ['name' => 'Kasir Dua', 'username' => 'kasir2', 'role' => 'kasir', 'is_active' => false])->assertSessionHasNoErrors();
        $this->assertFalse($cashier->fresh()->is_active);

        $this->put("/admin/user/{$cashier->id}/password", ['password' => 'barubaru123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('barubaru123', $cashier->fresh()->password));
    }

    public function test_store_toggle_and_settings(): void
    {
        $this->put('/admin/toko/status', ['is_open' => false])->assertSessionHasNoErrors();
        $this->assertFalse($this->settings->fresh()->is_open);

        $hours = collect(StoreSetting::DAYS)->mapWithKeys(fn ($d) => [$d => ['open' => '09:00', 'close' => '21:00', 'closed' => $d === 'sunday']])->all();
        $this->put('/admin/toko', [
            'store_name' => 'UMKM Mandiri',
            'whatsapp_number' => '0812 9999 8888',
            'min_order' => 15000,
            'operating_hours' => $hours,
            'online_order_enabled' => true,
            'allow_pickup' => true,
            'allow_delivery' => false,
        ])->assertSessionHasNoErrors();

        $settings = $this->settings->fresh();
        $this->assertSame('6281299998888', $settings->whatsapp_number);
        $this->assertSame(15000, $settings->min_order);
        $this->assertTrue($settings->operating_hours['sunday']['closed']);
        $this->assertFalse($settings->allow_delivery);
    }

    public function test_cashier_can_toggle_availability_and_it_applies_to_online_menu(): void
    {
        $product = Product::factory()->create();
        $cashier = $this->cashier();

        $this->actingAs($cashier)->patch("/pos/produk/{$product->id}/ketersediaan", ['is_available' => false])->assertSessionHasNoErrors();

        $this->get('/menu')->assertInertia(fn ($page) => $page->where('products.0.is_sold_out', true));
    }

    public function test_owner_sets_pin_with_password_confirmation(): void
    {
        $this->put('/akun/pin', ['current_password' => 'salah', 'pin' => '432187', 'pin_confirmation' => '432187'])->assertSessionHasErrors('current_password');
        $this->put('/akun/pin', ['current_password' => 'password', 'pin' => '4321', 'pin_confirmation' => '4321'])->assertSessionHasErrors('pin');
        $this->put('/akun/pin', ['current_password' => 'password', 'pin' => '432187', 'pin_confirmation' => '432187'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('432187', $this->owner->fresh()->pin_hash));
    }
}

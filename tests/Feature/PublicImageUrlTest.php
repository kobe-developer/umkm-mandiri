<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

/**
 * URL gambar harus relatif terhadap origin halaman, agar tetap tampil saat aplikasi
 * dibuka lewat host lain dari APP_URL (IP LAN, tunnel HTTPS seperti ngrok).
 */
class PublicImageUrlTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        config(['app.url' => 'http://localhost:8080']);
    }

    public function test_menu_exposes_qris_image_as_same_origin_url(): void
    {
        $this->qris->update(['qris_image_path' => 'qris/kode.webp']);

        $this->get('/menu')->assertInertia(fn (Assert $page) => $page
            ->where('paymentMethods', fn ($methods) => collect($methods)
                ->firstWhere('id', $this->qris->id)['qris_image_url'] === '/storage/qris/kode.webp'));
    }

    public function test_menu_exposes_product_photo_as_same_origin_url(): void
    {
        $product = Product::factory()->create(['photo_path' => 'products/ayam.webp']);

        $this->get('/menu')->assertInertia(fn (Assert $page) => $page
            ->where('products', fn ($products) => collect($products)
                ->firstWhere('id', $product->id)['photo_url'] === '/storage/products/ayam.webp'));
    }
}

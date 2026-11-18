<?php

namespace Tests\Feature;

use App\Enums\OrderChannel;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Event::fake([OrderCreated::class]);
    }

    private function promoPayload(Product $product, array $overrides = []): array
    {
        return [
            'product_id' => $product->id,
            'price' => 8000,
            'channels' => ['online', 'take_away'],
            'active_from' => today()->toDateString(),
            'active_until' => today()->addDays(3)->toDateString(),
            'description' => 'Promo HUT toko',
            ...$overrides,
        ];
    }

    public function test_owner_creates_promotion(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->actingAs($this->owner())
            ->post('/admin/promo', $this->promoPayload($product))
            ->assertSessionHasNoErrors();

        $promotion = Promotion::sole();
        $this->assertSame(8000, $promotion->price);
        $this->assertSame(['online', 'take_away'], $promotion->channels);
        $this->assertSame('Promo HUT toko', $promotion->description);
    }

    public function test_cashier_cannot_manage_promotions(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->cashier())->get('/admin/promo')->assertForbidden();
        $this->actingAs($this->cashier())->post('/admin/promo', $this->promoPayload($product))->assertForbidden();
        $this->assertSame(0, Promotion::count());
    }

    public function test_promo_price_must_be_lower_than_normal_price(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->actingAs($this->owner())
            ->post('/admin/promo', $this->promoPayload($product, ['price' => 10000]))
            ->assertSessionHasErrors('price');
        $this->assertSame(0, Promotion::count());
    }

    public function test_overlapping_period_for_same_product_is_rejected_regardless_of_channel(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        Promotion::factory()->for($product)->channels([OrderChannel::DineIn])
            ->between(today()->addDays(2)->toDateString(), today()->addDays(5)->toDateString())->create();

        $this->actingAs($this->owner())
            ->post('/admin/promo', $this->promoPayload($product, ['channels' => ['online']]))
            ->assertSessionHasErrors('active_from');

        // Periode yang tidak beririsan boleh
        $this->post('/admin/promo', $this->promoPayload($product, ['active_until' => today()->addDay()->toDateString()]))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Promotion::count());
    }

    public function test_updating_promotion_does_not_clash_with_itself_and_can_end_early(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        $promotion = Promotion::factory()->for($product)->create();

        $this->actingAs($this->owner())
            ->put("/admin/promo/{$promotion->id}", $this->promoPayload($product, [
                'active_from' => today()->subDays(3)->toDateString(),
                'active_until' => today()->subDay()->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(Promotion::STATUS_EXPIRED, $promotion->fresh()->status());
    }

    public function test_new_promotion_cannot_end_in_the_past(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->actingAs($this->owner())
            ->post('/admin/promo', $this->promoPayload($product, [
                'active_from' => today()->subDays(3)->toDateString(),
                'active_until' => today()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('active_until');
    }

    public function test_online_checkout_charges_promo_price_and_records_normal_price(): void
    {
        $product = Product::factory()->create(['price' => 13000]);
        $promotion = Promotion::factory()->for($product)->channels([OrderChannel::Online])->create(['price' => 10000]);

        $this->post('/menu/checkout', $this->checkoutPayload($product, 2))->assertSessionHasNoErrors();

        $order = Order::with('items')->sole();
        $item = $order->items->first();
        $this->assertSame(20000, $order->total);
        $this->assertSame(10000, $item->unit_price);
        $this->assertSame(13000, $item->original_price);
        $this->assertSame($promotion->id, $item->promotion_id);
        $this->assertSame(6000, $item->promoSaving());
    }

    public function test_promo_only_applies_to_its_channels(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShiftFor($cashier);
        $product = Product::factory()->create(['price' => 13000]);
        Promotion::factory()->for($product)->channels([OrderChannel::Online])->create(['price' => 10000]);

        $order = $this->placePosOrder($cashier, $shift, $product);

        $item = $order->items->first();
        $this->assertSame(13000, $item->unit_price);
        $this->assertSame(13000, $item->original_price);
        $this->assertNull($item->promotion_id);
    }

    public function test_promo_is_only_active_between_its_dates(): void
    {
        $scheduled = Product::factory()->create(['price' => 13000]);
        $expired = Product::factory()->create(['price' => 13000]);
        $lastDay = Product::factory()->create(['price' => 13000]);
        Promotion::factory()->for($scheduled)->between(today()->addDay()->toDateString(), today()->addDays(3)->toDateString())->create();
        Promotion::factory()->for($expired)->between(today()->subDays(3)->toDateString(), today()->subDay()->toDateString())->create();
        Promotion::factory()->for($lastDay)->between(today()->subDays(3)->toDateString(), today()->toDateString())->create();

        $this->travelTo(today()->setTime(23, 59, 30));
        $order = $this->placeOnlineOrder($scheduled);
        $this->assertSame(13000, $order->items->first()->unit_price, 'promo terjadwal belum berlaku');

        $order = $this->placeOnlineOrder($expired, 1, '081200000002');
        $this->assertSame(13000, $order->items->first()->unit_price, 'promo berakhir tidak berlaku');

        $order = $this->placeOnlineOrder($lastDay, 1, '081200000003');
        $this->assertSame(8000, $order->items->first()->unit_price, 'promo berlaku sampai akhir hari active_until');
    }

    public function test_promo_is_ignored_when_normal_price_drops_below_it(): void
    {
        $product = Product::factory()->create(['price' => 13000]);
        Promotion::factory()->for($product)->create(['price' => 10000]);
        $product->update(['price' => 9000]);

        $order = $this->placeOnlineOrder($product);

        $this->assertSame(9000, $order->items->first()->unit_price);
        $this->assertNull($order->items->first()->promotion_id);
    }

    public function test_menu_exposes_promo_and_normal_price(): void
    {
        $promo = Product::factory()->create(['price' => 13000, 'name' => 'Ayam Geprek']);
        $regular = Product::factory()->create(['price' => 5000, 'name' => 'Es Teh']);
        Promotion::factory()->for($promo)->channels([OrderChannel::Online])->create(['price' => 10000, 'description' => 'Promo buka cabang']);

        $this->get('/menu')->assertInertia(fn (Assert $page) => $page
            ->component('Menu/Index')
            ->where('products', fn ($products) => collect($products)->contains(fn ($p) => $p['id'] === $promo->id
                && $p['price'] === 10000 && $p['normal_price'] === 13000 && $p['promo_description'] === 'Promo buka cabang')
                && collect($products)->contains(fn ($p) => $p['id'] === $regular->id && $p['price'] === 5000 && $p['normal_price'] === null))
        );
    }

    public function test_menu_hides_promo_not_meant_for_online(): void
    {
        $product = Product::factory()->create(['price' => 13000]);
        Promotion::factory()->for($product)->channels([OrderChannel::DineIn])->create(['price' => 10000]);

        $this->get('/menu')->assertInertia(fn (Assert $page) => $page
            ->where('products.0.price', 13000)
            ->where('products.0.normal_price', null)
        );
    }

    public function test_admin_promotion_page_filters_by_status(): void
    {
        Promotion::factory()->create();
        Promotion::factory()->between(today()->subDays(5)->toDateString(), today()->subDay()->toDateString())->create();

        $this->actingAs($this->owner())->get('/admin/promo')->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Promotions')
            ->has('promotions.data', 1)
            ->where('promotions.data.0.status', Promotion::STATUS_ACTIVE)
        );
        $this->get('/admin/promo?status=semua')->assertInertia(fn (Assert $page) => $page->has('promotions.data', 2));
    }
}

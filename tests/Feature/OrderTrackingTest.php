<?php

namespace Tests\Feature;

use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderTracker;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

/** Pelacakan order oleh pelanggan: realtime, riwayat waktu status, dan kartu di halaman menu. */
class OrderTrackingTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    public function test_online_order_update_is_broadcast_to_the_customer_channel(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create());

        $channels = array_map(fn (Channel $c) => $c->name, (new OrderUpdated($order))->broadcastOn());

        $this->assertContains('private-orders', $channels);
        $this->assertContains("order.{$order->public_token}", $channels);
    }

    public function test_pos_order_update_is_only_broadcast_to_cashiers(): void
    {
        $cashier = $this->cashier();
        $order = $this->placePosOrder($cashier, $this->openShiftFor($cashier), Product::factory()->create());

        $channels = (new OrderUpdated($order))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
    }

    public function test_each_status_step_records_its_time(): void
    {
        Event::fake([OrderUpdated::class]);
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $order = $this->placeOnlineOrder(Product::factory()->create());
        $url = "/pos/orders/{$order->order_number}/advance";

        $this->travelTo(now()->addMinutes(2));
        $this->actingAs($cashier)->post($url);
        $this->travelTo(now()->addMinutes(3));
        $this->post($url);
        $this->travelTo(now()->addMinutes(10));
        $this->post($url);

        $fresh = $order->fresh();
        $this->assertSame('siap', $fresh->status->value);
        $this->assertTrue($fresh->confirmed_at->lt($fresh->processing_at));
        $this->assertTrue($fresh->processing_at->lt($fresh->ready_at));

        $times = $fresh->statusTimes();
        $this->assertTrue($times['baru']->equalTo($fresh->created_at));
        $this->assertTrue($times['siap']->equalTo($fresh->ready_at));
        $this->assertNull($times['selesai']);
    }

    public function test_pos_order_records_processing_time_on_creation(): void
    {
        $cashier = $this->cashier();
        $order = $this->placePosOrder($cashier, $this->openShiftFor($cashier), Product::factory()->create());

        $this->assertNotNull($order->processing_at);
    }

    public function test_status_page_exposes_realtime_channel_and_status_times(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create());

        $this->get("/menu/order/{$order->order_number}?t={$order->public_token}")
            ->assertInertia(fn ($page) => $page->component('Menu/OrderStatus', false)
                ->where('realtimeChannel', "order.{$order->public_token}")
                ->has('order.status_times.baru')
                ->where('order.status_times.dikonfirmasi', null));
    }

    public function test_menu_tracks_order_placed_by_this_visitor(): void
    {
        $order = $this->checkout();

        $this->get('/menu')->assertInertia(fn ($page) => $page->component('Menu/Index', false)
            ->where('hasOrderHistory', true)
            ->has('trackedOrders', 1)
            ->where('trackedOrders.0.order_number', $order->order_number)
            ->where('trackedOrders.0.status', 'baru')
            ->where('trackedOrders.0.realtime_channel', "order.{$order->public_token}")
            ->where('trackedOrders.0.status_url', route('menu.order.show', ['order' => $order->order_number, 't' => $order->public_token]))
            ->has('trackedOrders.0.status_time'));
    }

    public function test_menu_does_not_show_orders_from_other_visitors(): void
    {
        $this->placeOnlineOrder(Product::factory()->create());

        $this->get('/menu')->assertInertia(fn ($page) => $page->has('trackedOrders', 0)->where('hasOrderHistory', false));
        $this->get('/menu/pesanan')->assertInertia(fn ($page) => $page->has('orders', 0));
    }

    public function test_opening_status_link_starts_tracking_the_order(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create());

        $this->get("/menu/order/{$order->order_number}?t=salah")->assertNotFound();

        $this->keepTrackingCookie($this->get("/menu/order/{$order->order_number}?t={$order->public_token}")->assertOk());
        $this->get('/menu')->assertInertia(fn ($page) => $page->where('trackedOrders.0.order_number', $order->order_number));
    }

    public function test_menu_only_shows_orders_still_in_progress(): void
    {
        $finished = $this->checkout();
        $finished->update(['status' => 'selesai', 'payment_status' => 'lunas', 'completed_at' => now()]);
        $active = $this->checkout(['whatsapp_number' => '0812-0000-1111']);

        $this->get('/menu')->assertInertia(fn ($page) => $page
            ->has('trackedOrders', 1)
            ->where('trackedOrders.0.order_number', $active->order_number)
            ->where('hasOrderHistory', true));
    }

    public function test_history_page_lists_all_orders_of_this_visitor_newest_first(): void
    {
        $product = Product::factory()->create(['name' => 'Ayam Geprek', 'price' => 15000]);
        $first = $this->checkout([], $product, 2);
        $first->update(['status' => 'dibatalkan', 'cancelled_at' => now(), 'cancel_reason' => 'Stok habis']);
        $second = $this->checkout(['whatsapp_number' => '0812-0000-1111'], $product);

        $this->get('/menu/pesanan')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Menu/OrderHistory', false)
            ->has('orders', 2)
            ->where('orders.0.order_number', $second->order_number)
            ->where('orders.0.status', 'baru')
            ->where('orders.1.order_number', $first->order_number)
            ->where('orders.1.status', 'dibatalkan')
            ->where('orders.1.total', 30000)
            ->where('orders.1.items.0', ['name' => 'Ayam Geprek', 'quantity' => 2])
            ->has('orders.1.created_at')
            ->missing('orders.1.whatsapp_number'));
    }

    public function test_history_survives_a_new_session_on_the_same_device(): void
    {
        $order = $this->checkout();

        $this->flushSession();

        $this->get('/menu/pesanan')->assertInertia(fn ($page) => $page->where('orders.0.order_number', $order->order_number));
    }

    public function test_tampered_tracking_cookie_is_ignored(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create());

        $this->withUnencryptedCookie(OrderTracker::COOKIE, json_encode([$order->id]));

        $this->get('/menu/pesanan')->assertInertia(fn ($page) => $page->has('orders', 0));
    }

    /** Checkout lewat HTTP seperti pelanggan, lalu bawa cookie pelacakan ke request berikutnya. */
    private function checkout(array $overrides = [], ?Product $product = null, int $quantity = 1): Order
    {
        $response = $this->post('/menu/checkout', $this->checkoutPayload($product ?? Product::factory()->create(), $quantity, $overrides));
        $response->assertSessionHasNoErrors();
        $this->keepTrackingCookie($response);

        return Order::latest('id')->first();
    }

    private function keepTrackingCookie(TestResponse $response): void
    {
        $cookie = $response->getCookie(OrderTracker::COOKIE, decrypt: false);
        $this->assertNotNull($cookie, 'cookie pelacakan order dikirim ke browser');
        $this->withUnencryptedCookie(OrderTracker::COOKIE, $cookie->getValue());
    }
}

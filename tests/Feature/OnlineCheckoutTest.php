<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class OnlineCheckoutTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Event::fake([OrderCreated::class]);
    }

    public function test_checkout_creates_new_order_with_server_side_prices_and_customer(): void
    {
        $product = Product::factory()->create(['price' => 13000]);

        $response = $this->post('/menu/checkout', $this->checkoutPayload($product, 2, [
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 1, 'notes' => 'sambal dipisah']],
            'total' => 1,
        ]));

        $order = Order::with('items')->sole();
        $response->assertRedirect(route('menu.order.show', ['order' => $order->order_number, 't' => $order->public_token, 'baru' => 1]));

        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame(26000, $order->total, 'harga dihitung ulang dari tabel products');
        $this->assertSame('6281234567890', $order->whatsapp_number);
        $this->assertSame('sambal dipisah', $order->items->first()->notes);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-0001$/', $order->order_number);
        $this->assertSame(1, Customer::where('whatsapp_number', '6281234567890')->value('total_orders'));
        Event::assertDispatched(OrderCreated::class);
    }

    public function test_second_order_with_same_number_links_to_same_customer(): void
    {
        $product = Product::factory()->create();

        $this->post('/menu/checkout', $this->checkoutPayload($product));
        $this->post('/menu/checkout', $this->checkoutPayload($product, 1, ['whatsapp_number' => '+62 812 3456 7890', 'customer_name' => 'Budi S']));

        $this->assertSame(1, Customer::count());
        $customer = Customer::sole();
        $this->assertSame(2, $customer->total_orders);
        $this->assertSame('Budi S', $customer->name);
        $this->assertSame(2, Order::where('customer_id', $customer->id)->count());
        $this->assertSame(['ORD-'.now()->format('Ymd').'-0001', 'ORD-'.now()->format('Ymd').'-0002'], Order::orderBy('id')->pluck('order_number')->all());
    }

    public function test_checkout_is_rejected_when_store_is_closed_or_online_disabled(): void
    {
        $product = Product::factory()->create();

        $this->settings->update(['is_open' => false]);
        $this->post('/menu/checkout', $this->checkoutPayload($product))->assertSessionHasErrors(['store' => 'Toko sedang tutup.']);

        $this->settings->update(['is_open' => true, 'online_order_enabled' => false]);
        $this->post('/menu/checkout', $this->checkoutPayload($product))->assertSessionHasErrors('store');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_is_rejected_when_item_is_sold_out_or_hidden_online(): void
    {
        $soldOut = Product::factory()->create(['is_available' => false]);
        $noStock = Product::factory()->tracked(0)->create();
        $offline = Product::factory()->create(['show_online' => false]);

        foreach ([$soldOut, $noStock, $offline] as $product) {
            $this->post('/menu/checkout', $this->checkoutPayload($product))->assertSessionHasErrors('items');
        }

        $this->assertSame(0, Order::count());
    }

    public function test_anti_spam_limits_pending_orders_per_whatsapp_number(): void
    {
        $product = Product::factory()->create();
        config(['store.checkout_rate_limit_per_minute' => 100]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/menu/checkout', $this->checkoutPayload($product))->assertSessionHasNoErrors();
        }

        $this->post('/menu/checkout', $this->checkoutPayload($product))->assertSessionHasErrors('whatsapp_number');
        $this->assertSame(3, Order::count());
    }

    public function test_checkout_is_rate_limited_per_ip(): void
    {
        $product = Product::factory()->create();
        config(['store.checkout_rate_limit_per_minute' => 2]);

        $this->post('/menu/checkout', $this->checkoutPayload($product, 1, ['whatsapp_number' => '081111111111']));
        $this->post('/menu/checkout', $this->checkoutPayload($product, 1, ['whatsapp_number' => '081222222222']));
        $this->post('/menu/checkout', $this->checkoutPayload($product, 1, ['whatsapp_number' => '081333333333']))->assertSessionHasErrors('store');

        $this->assertSame(2, Order::count());
    }

    public function test_checkout_validates_required_fields(): void
    {
        $product = Product::factory()->create();

        $this->post('/menu/checkout', $this->checkoutPayload($product, 1, [
            'customer_name' => 'A',
            'whatsapp_number' => '021555',
            'fulfillment' => 'delivery',
            'address' => '',
            'consent' => false,
        ]))->assertSessionHasErrors(['customer_name', 'whatsapp_number', 'address', 'consent']);
    }

    public function test_minimum_order_and_online_payment_method_are_enforced(): void
    {
        $product = Product::factory()->create(['price' => 5000]);
        $this->settings->update(['min_order' => 10000]);
        $this->post('/menu/checkout', $this->checkoutPayload($product))->assertSessionHasErrors('items');

        $this->settings->update(['min_order' => 0]);
        $this->cash->update(['channels' => ['dine_in', 'take_away']]);
        $this->post('/menu/checkout', $this->checkoutPayload($product))->assertSessionHasErrors('payment_method_id');
    }

    public function test_status_page_requires_valid_token(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create());

        $this->get("/menu/order/{$order->order_number}")->assertNotFound();
        $this->get("/menu/order/{$order->order_number}?t=wrong")->assertNotFound();
        $this->get("/menu/order/{$order->order_number}?t={$order->public_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Menu/OrderStatus', false)
                ->where('order.order_number', $order->order_number)
                ->where('order.status', 'baru')
                ->missing('order.public_token'));
    }

    public function test_menu_page_lists_only_online_products(): void
    {
        $visible = Product::factory()->create(['name' => 'Nasi Goreng']);
        Product::factory()->create(['show_online' => false]);
        Product::factory()->create(['is_active' => false]);

        $this->get('/menu')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Menu/Index', false)
            ->has('products', 1)
            ->where('products.0.name', $visible->name)
            ->where('storeSettings.accepts_orders', true));
    }
}

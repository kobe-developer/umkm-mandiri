<?php

namespace Tests\Feature;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOnlineOrderPush;
use App\Notifications\OrderStatusPush;
use App\Services\OrderLifecycleService;
use App\Services\PushSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\PushSubscription;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/abc123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Event::fake([OrderCreated::class, OrderUpdated::class]);
        Notification::fake();
        config(['webpush.vapid.public_key' => 'public-key', 'webpush.vapid.private_key' => 'private-key']);
    }

    /** @return array<string, mixed> */
    private function subscription(string $endpoint = self::ENDPOINT): array
    {
        return ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'BPublicKey', 'auth' => 'authSecret'], 'content_encoding' => 'aes128gcm'];
    }

    private function subscribe(User $user, string $endpoint = self::ENDPOINT): void
    {
        app(PushSubscriptionService::class)->subscribeUser($user, $this->subscription($endpoint));
    }

    // --- Langganan perangkat staf ---

    public function test_staff_can_subscribe_and_unsubscribe_device(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->postJson('/push-subscriptions', $this->subscription())->assertOk();
        $this->assertSame(self::ENDPOINT, $cashier->pushSubscriptions()->sole()->endpoint);

        $this->deleteJson('/push-subscriptions', ['endpoint' => self::ENDPOINT])->assertOk();
        $this->assertSame(0, $cashier->pushSubscriptions()->count());
    }

    public function test_guest_cannot_subscribe_as_staff(): void
    {
        $this->postJson('/push-subscriptions', $this->subscription())->assertUnauthorized();
        $this->assertSame(0, PushSubscription::count());
    }

    public function test_shared_device_moves_to_the_last_logged_in_user(): void
    {
        $first = $this->cashier();
        $second = $this->owner();

        $this->actingAs($first)->postJson('/push-subscriptions', $this->subscription())->assertOk();
        $this->actingAs($second)->postJson('/push-subscriptions', $this->subscription())->assertOk();

        $this->assertSame(0, $first->pushSubscriptions()->count());
        $this->assertSame(1, $second->pushSubscriptions()->count());
    }

    public function test_endpoint_must_belong_to_a_known_push_service(): void
    {
        $cashier = $this->cashier();
        $invalid = [
            'http://fcm.googleapis.com/fcm/send/x',
            'https://localhost/internal',
            'https://169.254.169.254/latest/meta-data',
            'https://fcm.googleapis.com.evil.test/x',
            'https://fcm.googleapis.com:8443/x',
            'https://user@fcm.googleapis.com/x',
        ];

        foreach ($invalid as $endpoint) {
            $this->actingAs($cashier)->postJson('/push-subscriptions', $this->subscription($endpoint))
                ->assertUnprocessable()->assertJsonValidationErrors('endpoint');
        }

        foreach (['https://web.push.apple.com/abc', 'https://updates.push.services.mozilla.com/wpush/v2/x', 'https://wns2-sg2p.notify.windows.com/w/?token=x'] as $endpoint) {
            $this->postJson('/push-subscriptions', $this->subscription($endpoint))->assertOk();
        }
    }

    // --- Langganan pelanggan saat checkout ---

    public function test_checkout_with_push_subscription_attaches_it_to_the_order(): void
    {
        $product = Product::factory()->create();

        $this->post('/menu/checkout', $this->checkoutPayload($product, 1, ['push_subscription' => $this->subscription()]))
            ->assertSessionHasNoErrors();

        $this->assertSame(self::ENDPOINT, Order::sole()->pushSubscriptions()->sole()->endpoint);
    }

    public function test_same_device_can_follow_several_orders(): void
    {
        $product = Product::factory()->create();

        $this->post('/menu/checkout', $this->checkoutPayload($product, 1, ['push_subscription' => $this->subscription()]));
        $this->post('/menu/checkout', $this->checkoutPayload($product, 1, ['push_subscription' => $this->subscription()]));

        $this->assertSame(2, Order::count());
        Order::all()->each(fn (Order $order) => $this->assertSame(1, $order->pushSubscriptions()->count()));
    }

    public function test_checkout_rejects_untrusted_push_endpoint(): void
    {
        $product = Product::factory()->create();

        $this->post('/menu/checkout', $this->checkoutPayload($product, 1, ['push_subscription' => $this->subscription('https://attacker.test/hook')]))
            ->assertSessionHasErrors('push_subscription.endpoint');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_without_push_subscription_still_works(): void
    {
        $product = Product::factory()->create();

        $this->post('/menu/checkout', $this->checkoutPayload($product))->assertSessionHasNoErrors();

        $this->assertSame(0, PushSubscription::count());
    }

    // --- Push order online baru ke staf ---

    public function test_new_online_order_notifies_subscribed_active_staff_only(): void
    {
        $owner = $this->owner();
        $cashier = $this->cashier();
        $inactive = User::factory()->create(['is_active' => false]);
        $notSubscribed = $this->cashier();
        $this->subscribe($owner, 'https://fcm.googleapis.com/fcm/send/owner');
        $this->subscribe($cashier, 'https://fcm.googleapis.com/fcm/send/cashier');
        $this->subscribe($inactive, 'https://fcm.googleapis.com/fcm/send/inactive');

        $order = $this->placeOnlineOrder(Product::factory()->create());

        Notification::assertSentTo([$owner, $cashier], NewOnlineOrderPush::class, fn ($n) => $n->order->is($order));
        Notification::assertNotSentTo([$inactive, $notSubscribed], NewOnlineOrderPush::class);
    }

    public function test_pos_order_does_not_notify_staff(): void
    {
        $cashier = $this->cashier();
        $this->subscribe($cashier);

        $this->placePosOrder($cashier, $this->openShiftFor($cashier), Product::factory()->create());

        Notification::assertNothingSent();
    }

    public function test_nothing_is_sent_when_vapid_keys_are_missing(): void
    {
        config(['webpush.vapid.public_key' => null]);
        $this->subscribe($this->owner());

        $this->placeOnlineOrder(Product::factory()->create());

        Notification::assertNothingSent();
    }

    public function test_new_order_push_message_links_to_order_detail(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create(['price' => 13000]), 2);

        $message = (new NewOnlineOrderPush($order))->toWebPush($this->owner(), new NewOnlineOrderPush($order))->toArray();

        $this->assertSame("Order online baru · {$order->order_number}", $message['title']);
        $this->assertStringContainsString('Budi', $message['body']);
        $this->assertStringContainsString('Rp26.000', $message['body']);
        $this->assertSame("/pos/orders/{$order->order_number}", $message['data']['url']);
    }

    // --- Push status ke pelanggan ---

    private function subscribedOnlineOrder(): Order
    {
        $order = $this->placeOnlineOrder(Product::factory()->create(['price' => 10000]));
        app(PushSubscriptionService::class)->subscribeOrder($order, $this->subscription());

        return $order;
    }

    public function test_customer_is_notified_of_each_status_and_payment(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShiftFor($cashier);
        $order = $this->subscribedOnlineOrder();
        $lifecycle = app(OrderLifecycleService::class);

        $lifecycle->advance($order, $cashier);
        $lifecycle->advance($order, $cashier);
        $lifecycle->advance($order, $cashier);
        $lifecycle->pay($order, $this->cash->id, 10000, $cashier, $shift);
        $lifecycle->advance($order, $cashier);

        $events = [];
        Notification::assertSentTo($order, OrderStatusPush::class, function (OrderStatusPush $n) use (&$events) {
            $events[] = $n->event;

            return true;
        });
        $this->assertSame(['dikonfirmasi', 'diproses', 'siap', OrderStatusPush::EVENT_PAID, 'selesai'], $events);
    }

    public function test_customer_is_notified_when_order_is_cancelled(): void
    {
        $order = $this->subscribedOnlineOrder();

        app(OrderLifecycleService::class)->cancel($order, 'Stok habis', $this->cashier());

        Notification::assertSentTo($order, OrderStatusPush::class, fn (OrderStatusPush $n) => $n->event === OrderStatus::Cancelled->value);
    }

    public function test_editing_items_does_not_notify_customer(): void
    {
        $order = $this->subscribedOnlineOrder();
        $product = Product::factory()->create();

        app(OrderLifecycleService::class)->updateItems($order, [['product_id' => $product->id, 'quantity' => 1]], $this->cashier());

        Notification::assertNotSentTo($order, OrderStatusPush::class);
    }

    public function test_order_without_subscription_sends_nothing(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create());

        app(OrderLifecycleService::class)->advance($order, $this->cashier());

        Notification::assertNothingSent();
    }

    public function test_status_message_matches_fulfillment_and_links_to_status_page(): void
    {
        $order = $this->subscribedOnlineOrder();
        $order->update(['fulfillment' => Fulfillment::Delivery, 'address' => 'Jl. Melati']);

        $message = (new OrderStatusPush($order, OrderStatus::Ready->value))->toWebPush($order, new OrderStatusPush($order, 'siap'))->toArray();

        $this->assertSame('Pesanan sedang diantar', $message['title']);
        $this->assertSame("/menu/order/{$order->order_number}?t={$order->public_token}", $message['data']['url']);
        $this->assertSame("order-{$order->order_number}", $message['tag']);
    }

    public function test_finished_orders_cannot_subscribe_and_old_subscriptions_are_pruned(): void
    {
        $order = $this->subscribedOnlineOrder();
        $service = app(PushSubscriptionService::class);
        app(OrderLifecycleService::class)->cancel($order, 'Batal', $this->cashier());

        $this->assertNull($service->subscribeOrder($order->fresh(), $this->subscription('https://fcm.googleapis.com/fcm/send/other')));

        $this->assertSame(0, $service->pruneFinishedOrders(), 'belum lewat masa simpan');
        $this->travel(config('store.push_order_retention_days') + 1)->days();
        $this->assertSame(1, $service->pruneFinishedOrders());
    }

    public function test_device_limit_per_order(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create());
        $service = app(PushSubscriptionService::class);
        $max = config('store.push_max_devices_per_order');

        for ($i = 0; $i <= $max; $i++) {
            $service->subscribeOrder($order, $this->subscription("https://fcm.googleapis.com/fcm/send/device-{$i}"));
        }

        $this->assertSame($max, $order->pushSubscriptions()->count());
    }

    // --- PWA ---

    public function test_manifests_use_store_name_and_start_url(): void
    {
        $this->settings->update(['store_name' => 'Warung Bu Sri']);

        $this->get('/manifest.webmanifest')->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'Warung Bu Sri')
            ->assertJsonPath('start_url', '/menu');
        $this->get('/manifest-pos.webmanifest')->assertOk()->assertJsonPath('start_url', '/pos');
    }
}

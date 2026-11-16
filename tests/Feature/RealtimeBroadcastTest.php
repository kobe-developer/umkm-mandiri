<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class RealtimeBroadcastTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    public function test_order_events_broadcast_immediately_without_waiting_for_queue_worker(): void
    {
        $this->assertInstanceOf(ShouldBroadcastNow::class, new OrderCreated(new Order));
        $this->assertInstanceOf(ShouldBroadcastNow::class, new OrderUpdated(new Order));
    }

    public function test_online_order_does_not_push_broadcast_job_to_queue(): void
    {
        Queue::fake();
        $product = Product::factory()->create();

        $this->post('/menu/checkout', $this->checkoutPayload($product))->assertSessionHasNoErrors();

        Queue::assertNothingPushed();
    }

    public function test_checkout_still_succeeds_when_websocket_server_is_down(): void
    {
        $this->useFailingBroadcaster();
        $product = Product::factory()->create();

        $response = $this->post('/menu/checkout', $this->checkoutPayload($product));

        $order = Order::sole();
        $response->assertRedirect(route('menu.order.show', ['order' => $order->order_number, 't' => $order->public_token, 'baru' => 1]));
    }

    public function test_cashier_action_still_succeeds_when_websocket_server_is_down(): void
    {
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $order = $this->placeOnlineOrder(Product::factory()->create());
        $this->useFailingBroadcaster();

        $this->actingAs($cashier)->post("/pos/orders/{$order->order_number}/advance")->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }

    private function useFailingBroadcaster(): void
    {
        Broadcast::extend('failing', fn () => new class extends Broadcaster
        {
            public function auth($request) {}

            public function validAuthenticationResponse($request, $result) {}

            public function broadcast(array $channels, $event, array $payload = []): void
            {
                throw new BroadcastException('Reverb tidak dapat dihubungi');
            }
        });
        config([
            'broadcasting.connections.failing' => ['driver' => 'failing'],
            'broadcasting.default' => 'failing',
        ]);
    }
}

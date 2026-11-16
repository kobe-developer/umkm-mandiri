<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Event::fake([OrderCreated::class, OrderUpdated::class]);
    }

    public function test_online_order_full_flow_from_new_to_completed(): void
    {
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $product = Product::factory()->tracked(5)->create(['price' => 10000]);
        $order = $this->placeOnlineOrder($product, 2);
        $url = "/pos/orders/{$order->order_number}";

        $this->actingAs($cashier)->post("{$url}/pay", ['payment_method_id' => $this->cash->id, 'amount_received' => 20000])
            ->assertSessionHasErrors('payment');

        $this->post("{$url}/advance")->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->confirmed_at);

        $this->post("{$url}/advance");
        $this->post("{$url}/advance");
        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);

        $this->post("{$url}/advance")->assertSessionHasErrors('payment');

        $this->post("{$url}/pay", ['payment_method_id' => $this->cash->id, 'amount_received' => 50000])->assertSessionHasNoErrors();
        $this->assertSame(30000, $order->fresh()->change_amount);
        $this->assertSame(3, $product->fresh()->stock);

        $this->post("{$url}/advance")->assertSessionHasNoErrors();
        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Completed, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        Event::assertDispatched(OrderUpdated::class);

        $this->post("{$url}/advance")->assertSessionHasErrors('status');
    }

    public function test_cashier_can_edit_items_before_confirmation_only(): void
    {
        $cashier = $this->cashier();
        $first = Product::factory()->create(['price' => 10000]);
        $second = Product::factory()->create(['price' => 4000]);
        $order = $this->placeOnlineOrder($first, 1);
        $url = "/pos/orders/{$order->order_number}/items";

        $this->actingAs($cashier)->put($url, ['items' => [['product_id' => $second->id, 'quantity' => 3]]])->assertSessionHasNoErrors();
        $this->assertSame(12000, $order->fresh()->total);
        $this->assertSame([$second->id], $order->items()->pluck('product_id')->all());

        $this->post("/pos/orders/{$order->order_number}/advance");
        $this->put($url, ['items' => [['product_id' => $first->id, 'quantity' => 1]]])->assertSessionHasErrors('items');
    }

    public function test_unpaid_order_can_be_cancelled_with_reason(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create());
        $this->actingAs($this->cashier());

        $this->post("/pos/orders/{$order->order_number}/cancel", [])->assertSessionHasErrors('reason');
        $this->post("/pos/orders/{$order->order_number}/cancel", ['reason' => 'Menu habis'])->assertSessionHasNoErrors();

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Cancelled, $fresh->status);
        $this->assertSame('Menu habis', $fresh->cancel_reason);
    }

    public function test_paid_order_cannot_be_cancelled_and_must_be_voided(): void
    {
        $cashier = $this->cashier();
        $order = $this->placePosOrder($cashier, $this->openShiftFor($cashier), Product::factory()->create());

        $this->actingAs($cashier)->post("/pos/orders/{$order->order_number}/cancel", ['reason' => 'salah input'])
            ->assertSessionHasErrors('status');
    }

    public function test_cashier_void_requires_correct_owner_pin_and_restores_stock(): void
    {
        $this->owner();
        $cashier = $this->cashier();
        $product = Product::factory()->tracked(5)->create();
        $order = $this->placePosOrder($cashier, $this->openShiftFor($cashier), $product, 2);
        $this->assertSame(3, $product->fresh()->stock);
        $url = "/pos/orders/{$order->order_number}/void";

        $this->actingAs($cashier)->post($url, ['reason' => 'salah input'])->assertSessionHasErrors('pin');
        $this->post($url, ['reason' => 'salah input', 'pin' => '000000'])->assertSessionHasErrors('pin');
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);

        $this->post($url, ['reason' => 'salah input', 'pin' => '123456'])->assertSessionHasNoErrors();

        $fresh = $order->fresh();
        $this->assertSame(PaymentStatus::Void, $fresh->payment_status);
        $this->assertSame(OrderStatus::Cancelled, $fresh->status);
        $this->assertSame($cashier->id, $fresh->voided_by);
        $this->assertNotNull($fresh->void_approved_by);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'retur', 'quantity' => 2]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'void', 'subject_id' => $order->id]);
    }

    public function test_pin_attempts_are_rate_limited(): void
    {
        $this->owner();
        $cashier = $this->cashier();
        $order = $this->placePosOrder($cashier, $this->openShiftFor($cashier), Product::factory()->create());
        $url = "/pos/orders/{$order->order_number}/void";
        config(['store.pin_max_attempts' => 2]);

        $this->actingAs($cashier);
        $this->post($url, ['reason' => 'x x x', 'pin' => '111111']);
        $this->post($url, ['reason' => 'x x x', 'pin' => '222222']);
        $this->post($url, ['reason' => 'x x x', 'pin' => '123456'])->assertSessionHasErrors(['pin']);

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_owner_can_void_without_pin(): void
    {
        $owner = $this->owner();
        $order = $this->placePosOrder($owner, $this->openShiftFor($owner), Product::factory()->create());

        $this->actingAs($owner)->post("/pos/orders/{$order->order_number}/void", ['reason' => 'refund pelanggan'])->assertSessionHasNoErrors();
        $this->assertSame(PaymentStatus::Void, $order->fresh()->payment_status);
    }

    public function test_payment_method_must_support_order_channel(): void
    {
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $transferOnly = $this->qris->replicate()->fill(['name' => 'Transfer', 'channels' => ['online']]);
        $transferOnly->save();
        $order = $this->placePosOrder($cashier, $cashier->openShift, Product::factory()->create(), 1, ['channel' => 'dine_in', 'pay_later' => true]);

        $this->actingAs($cashier)->post("/pos/orders/{$order->order_number}/pay", ['payment_method_id' => $transferOnly->id])
            ->assertSessionHasErrors('payment_method_id');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class PosOrderTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    private function payload(Product $product, int $quantity = 2, array $overrides = []): array
    {
        return [
            'channel' => 'dine_in',
            'table_number' => '5',
            'items' => [['product_id' => $product->id, 'quantity' => $quantity, 'notes' => 'level pedas 3']],
            'payment_method_id' => $this->cash->id,
            'amount_received' => 50000,
            'pay_later' => false,
            ...$overrides,
        ];
    }

    public function test_transactions_require_an_open_shift(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->cashier())->post('/pos/orders', $this->payload($product))->assertSessionHasErrors('shift');
        $this->assertSame(0, Order::count());
    }

    public function test_cash_order_is_paid_with_change_and_deducts_stock(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShiftFor($cashier);
        $product = Product::factory()->tracked(10)->create(['price' => 13000]);

        $this->actingAs($cashier)->post('/pos/orders', $this->payload($product))->assertSessionHasNoErrors();

        $order = Order::with('items')->sole();
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(26000, $order->total);
        $this->assertSame(24000, $order->change_amount);
        $this->assertSame($shift->id, $order->shift_id);
        $this->assertSame('5', $order->table_number);
        $this->assertSame(13000, $order->items->first()->unit_price);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'keluar', 'quantity' => -2, 'order_id' => $order->id]);
    }

    public function test_cash_received_less_than_total_is_rejected(): void
    {
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $product = Product::factory()->create(['price' => 13000]);

        $this->actingAs($cashier)->post('/pos/orders', $this->payload($product, 2, ['amount_received' => 20000]))
            ->assertSessionHasErrors('amount_received');
        $this->assertSame(0, Order::count(), 'transaksi di-rollback');
    }

    public function test_insufficient_stock_rolls_back_the_order(): void
    {
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $product = Product::factory()->tracked(1)->create();

        $this->actingAs($cashier)->post('/pos/orders', $this->payload($product, 2))->assertSessionHasErrors('stock');
        $this->assertSame(0, Order::count());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_dine_in_can_be_saved_and_paid_later_without_touching_stock(): void
    {
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $product = Product::factory()->tracked(5)->create();

        $this->actingAs($cashier)->post('/pos/orders', $this->payload($product, 2, ['pay_later' => true, 'payment_method_id' => null]))
            ->assertSessionHasNoErrors();

        $order = Order::sole();
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame(5, $product->fresh()->stock, 'stok berkurang saat lunas, bukan saat order dibuat');

        $this->actingAs($cashier)->post("/pos/orders/{$order->order_number}/pay", ['payment_method_id' => $this->qris->id])->assertSessionHasNoErrors();
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_pay_later_is_ignored_for_take_away(): void
    {
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $product = Product::factory()->create();

        $this->actingAs($cashier)->post('/pos/orders', $this->payload($product, 1, ['channel' => 'take_away', 'pay_later' => true, 'payment_method_id' => null]))
            ->assertSessionHasErrors('payment_method_id');
    }

    public function test_cashier_discount_is_ignored_unless_allowed_by_owner(): void
    {
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $product = Product::factory()->create(['price' => 10000]);

        $this->actingAs($cashier)->post('/pos/orders', $this->payload($product, 2, ['discount_type' => 'percent', 'discount_value' => 50]));
        $this->assertSame(20000, Order::latest('id')->first()->total);

        $this->settings->update(['cashier_can_discount' => true]);
        $this->actingAs($cashier)->post('/pos/orders', $this->payload($product, 2, ['discount_type' => 'percent', 'discount_value' => 50]));
        $order = Order::latest('id')->first();
        $this->assertSame(10000, $order->discount);
        $this->assertSame(10000, $order->total);
    }

    public function test_sold_out_products_cannot_be_ordered(): void
    {
        $cashier = $this->cashier();
        $this->openShiftFor($cashier);
        $product = Product::factory()->create(['is_available' => false]);

        $this->actingAs($cashier)->post('/pos/orders', $this->payload($product))->assertSessionHasErrors('items');
    }

    public function test_price_change_does_not_alter_existing_transactions(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShiftFor($cashier);
        $product = Product::factory()->create(['price' => 10000]);
        $order = $this->placePosOrder($cashier, $shift, $product);

        $product->update(['price' => 15000]);

        $this->assertSame(10000, $order->items()->first()->unit_price);
        $this->assertSame(10000, $order->fresh()->total);
    }
}

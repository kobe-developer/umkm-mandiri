<?php

namespace Tests\Feature;

use App\Exports\TransactionsExport;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    public function test_spoofed_forwarded_for_header_does_not_bypass_checkout_rate_limit(): void
    {
        $product = Product::factory()->create();
        config(['store.checkout_rate_limit_per_minute' => 2]);

        foreach (['081111111111', '081222222222', '081333333333'] as $i => $wa) {
            $this->withHeader('X-Forwarded-For', "10.0.0.{$i}")
                ->post('/menu/checkout', $this->checkoutPayload($product, 1, ['whatsapp_number' => $wa]));
        }

        $this->assertSame(2, Order::count());
    }

    public function test_spoofed_forwarded_for_header_does_not_bypass_login_throttle(): void
    {
        User::factory()->owner()->create(['username' => 'bos']);

        for ($i = 0; $i < 5; $i++) {
            $this->withHeader('X-Forwarded-For', "10.0.1.{$i}")->post('/login', ['username' => 'bos', 'password' => 'salah']);
        }

        $this->withHeader('X-Forwarded-For', '10.0.1.99')
            ->post('/login', ['username' => 'bos', 'password' => 'password'])
            ->assertStatus(429);
    }

    public function test_excel_export_neutralises_formulas_from_customer_input(): void
    {
        $order = $this->placeOnlineOrder(Product::factory()->create());
        $order->update(['customer_name' => '=HYPERLINK("http://evil/x","Buka")']);

        $row = (new TransactionsExport(Order::query()))->map($order->fresh(['items', 'paymentMethod', 'cashier']));

        $this->assertSame('\'=HYPERLINK("http://evil/x","Buka")', $row[6]);
        $this->assertSame($order->total, $row[12], 'angka tetap angka');
    }

    public function test_owner_pin_has_global_lockout_across_cashiers(): void
    {
        $this->owner();
        config(['store.pin_max_attempts_global' => 3]);
        $orders = collect(range(1, 2))->map(function () {
            $cashier = $this->cashier();

            return [$cashier, $this->placePosOrder($cashier, $this->openShiftFor($cashier), Product::factory()->create())];
        });

        [$first, $firstOrder] = $orders[0];
        [$second, $secondOrder] = $orders[1];
        $this->actingAs($first)->post("/pos/orders/{$firstOrder->order_number}/void", ['reason' => 'tes', 'pin' => '000001']);
        $this->post("/pos/orders/{$firstOrder->order_number}/void", ['reason' => 'tes', 'pin' => '000002']);
        $this->actingAs($second)->post("/pos/orders/{$secondOrder->order_number}/void", ['reason' => 'tes', 'pin' => '000003']);

        $this->post("/pos/orders/{$secondOrder->order_number}/void", ['reason' => 'tes', 'pin' => '123456'])
            ->assertSessionHasErrors(['pin']);
        $this->assertTrue($secondOrder->fresh()->isPaid());
        $this->assertDatabaseCount('activity_logs', 3);
    }
}

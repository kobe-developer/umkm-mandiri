<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    public function test_cashier_opens_shift_once(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->post('/pos/shift', ['opening_cash' => 200000])->assertRedirect('/pos/kasir');
        $this->post('/pos/shift', ['opening_cash' => 100000])->assertSessionHasErrors('shift');

        $this->assertSame(1, Shift::count());
        $this->assertSame(200000, $cashier->fresh()->openShift->opening_cash);
    }

    public function test_closing_shift_calculates_cash_difference_from_cash_sales_only(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShiftFor($cashier, 100000);
        $product = Product::factory()->create(['price' => 10000]);

        $this->placePosOrder($cashier, $shift, $product, 3);
        $this->placePosOrder($cashier, $shift, $product, 2, ['payment_method_id' => $this->qris->id]);

        $this->actingAs($cashier)->put('/pos/shift', ['closing_cash_actual' => 125000])->assertSessionHasNoErrors();

        $closed = $shift->fresh();
        $this->assertNotNull($closed->closed_at);
        $this->assertSame(130000, $closed->closing_cash_system);
        $this->assertSame(-5000, $closed->cash_difference);
        $this->assertNull($cashier->fresh()->openShift);
    }

    public function test_voided_cash_sales_are_excluded_from_expected_cash(): void
    {
        $owner = $this->owner();
        $shift = $this->openShiftFor($owner, 0);
        $order = $this->placePosOrder($owner, $shift, Product::factory()->create(['price' => 10000]));
        $this->actingAs($owner)->post("/pos/orders/{$order->order_number}/void", ['reason' => 'batal']);

        $this->put('/pos/shift', ['closing_cash_actual' => 0]);
        $this->assertSame(0, $shift->fresh()->cash_difference);
    }
}

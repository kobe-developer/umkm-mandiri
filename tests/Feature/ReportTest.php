<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\OrderLifecycleService;
use App\Services\ReportService;
use App\Support\ReportPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    public function test_revenue_counts_only_completed_and_paid_orders(): void
    {
        $owner = $this->owner();
        $shift = $this->openShiftFor($owner);
        $lifecycle = app(OrderLifecycleService::class);
        $product = Product::factory()->create(['price' => 10000]);

        $completed = $this->placePosOrder($owner, $shift, $product, 2);
        $lifecycle->complete($completed, $owner);

        $this->placePosOrder($owner, $shift, $product, 5); // lunas tapi belum selesai
        $this->placeOnlineOrder($product, 3); // baru, belum bayar

        $voided = $this->placePosOrder($owner, $shift, $product, 1);
        $lifecycle->complete($voided, $owner);
        $lifecycle->void($voided->fresh(), 'refund', $owner, null);

        $summary = app(ReportService::class)->summary(ReportPeriod::make('today'));

        $this->assertSame(['revenue' => 20000, 'transactions' => 1, 'average' => 20000], $summary);
    }

    public function test_breakdowns_by_channel_product_and_payment_method(): void
    {
        $owner = $this->owner();
        $shift = $this->openShiftFor($owner);
        $lifecycle = app(OrderLifecycleService::class);
        $nasi = Product::factory()->create(['name' => 'Nasi Goreng', 'price' => 10000]);
        $teh = Product::factory()->create(['name' => 'Es Teh Jumbo', 'price' => 4000]);

        foreach ([[$nasi, 2, 'take_away'], [$teh, 5, 'dine_in'], [$nasi, 1, 'dine_in']] as [$product, $qty, $channel]) {
            $lifecycle->complete($this->placePosOrder($owner, $shift, $product, $qty, ['channel' => $channel]), $owner);
        }

        $reports = app(ReportService::class);
        $period = ReportPeriod::make('today');

        $channels = collect($reports->byChannel($period))->keyBy('key');
        $this->assertSame(30000, $channels['dine_in']['revenue']);
        $this->assertSame(20000, $channels['take_away']['revenue']);
        $this->assertSame(0, $channels['online']['revenue']);

        $top = $reports->topProducts($period);
        $this->assertSame('Es Teh Jumbo', $top[0]['name']);
        $this->assertSame(5, $top[0]['quantity']);
        $this->assertSame('Nasi Goreng', $reports->topProducts($period, 'revenue')[0]['name']);

        $this->assertSame(50000, $reports->byPaymentMethod($period)[0]['revenue']);
        $this->assertSame(50000, $reports->byCashier($period)[0]['revenue']);
        $this->assertCount(24, $reports->hourly($period));
        $this->assertSame(50000, array_sum(array_column($reports->daily(ReportPeriod::make('7d')), 'revenue')));
    }

    public function test_cashier_dashboard_hides_revenue_when_not_allowed(): void
    {
        $cashier = $this->cashier();
        $this->settings->update(['cashier_can_view_revenue' => false]);

        $this->actingAs($cashier)->get('/pos')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Pos/Dashboard', false)
            ->missing('summary.revenue')
            ->has('summary.transactions')
            ->where('pos.can_view_revenue', false));
    }

    public function test_owner_can_export_transactions(): void
    {
        $owner = $this->owner();
        $this->placePosOrder($owner, $this->openShiftFor($owner), Product::factory()->create());

        $this->actingAs($owner)->get('/admin/transaksi/export/xlsx?period=today')->assertOk()->assertDownload();
        $this->get('/admin/transaksi/export/pdf?period=today')->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}

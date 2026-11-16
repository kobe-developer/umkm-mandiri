<?php

namespace Tests\Unit;

use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ReportPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-11-16 14:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_presets_produce_expected_ranges(): void
    {
        $this->assertSame(['preset' => 'today', 'from' => '2026-11-16', 'to' => '2026-11-16'], ReportPeriod::make('today')->toArray());
        $this->assertSame('2026-11-10', ReportPeriod::make('7d')->toArray()['from']);
        $this->assertSame('2026-10-18', ReportPeriod::make('30d')->toArray()['from']);
        $this->assertSame('2026-11-01', ReportPeriod::make('month')->toArray()['from']);
        $this->assertSame('today', ReportPeriod::make('nonsense')->preset);
    }

    public function test_previous_period_has_same_length_and_ends_before_start(): void
    {
        $period = ReportPeriod::make('7d');
        $previous = $period->previous();

        $this->assertSame(7, $previous->days());
        $this->assertSame('2026-11-03', $previous->start->toDateString());
        $this->assertSame('2026-11-09 23:59:59', $previous->end->toDateTimeString());
    }

    public function test_custom_range_is_swapped_when_reversed_and_capped(): void
    {
        $period = ReportPeriod::make('custom', '2026-11-10', '2026-11-01');
        $this->assertSame('2026-11-01', $period->toArray()['from']);
        $this->assertSame('2026-11-10', $period->toArray()['to']);

        $long = ReportPeriod::make('custom', '2020-01-01', '2026-11-16');
        $this->assertLessThanOrEqual(ReportPeriod::MAX_CUSTOM_DAYS + 1, $long->days());
    }
}

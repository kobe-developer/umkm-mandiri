<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Services\StoreStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function settings(array $hours, bool $isOpen = false): StoreSetting
    {
        $all = collect(StoreSetting::DAYS)->mapWithKeys(fn ($d) => [$d => $hours])->all();
        $settings = StoreSetting::current();
        $settings->update(['operating_hours' => $all, 'is_open' => $isOpen, 'auto_schedule' => true, 'last_schedule_state' => null]);

        return $settings;
    }

    public function test_store_opens_and_closes_automatically(): void
    {
        $service = app(StoreStatusService::class);
        $this->settings(['open' => '10:00', 'close' => '22:00', 'closed' => false]);

        $this->assertTrue($service->syncWithSchedule(Carbon::parse('2026-11-16 10:00')));
        $this->assertTrue(StoreSetting::current()->is_open);

        $this->assertFalse($service->syncWithSchedule(Carbon::parse('2026-11-16 15:00')), 'tidak ada perubahan di tengah jadwal');

        $this->assertTrue($service->syncWithSchedule(Carbon::parse('2026-11-16 22:00')));
        $this->assertFalse(StoreSetting::current()->is_open);
    }

    public function test_manual_override_lasts_until_next_schedule_boundary(): void
    {
        $service = app(StoreStatusService::class);
        $this->settings(['open' => '10:00', 'close' => '22:00', 'closed' => false]);
        $service->syncWithSchedule(Carbon::parse('2026-11-16 11:00'));

        $service->setOpen(false); // tutup lebih awal (misal bahan habis)
        $service->syncWithSchedule(Carbon::parse('2026-11-16 12:00'));
        $this->assertFalse(StoreSetting::current()->is_open, 'override manual dipertahankan');

        $service->syncWithSchedule(Carbon::parse('2026-11-16 23:00'));
        $service->syncWithSchedule(Carbon::parse('2026-11-17 10:00'));
        $this->assertTrue(StoreSetting::current()->is_open, 'jadwal berikutnya berlaku lagi');
    }

    public function test_supports_closing_after_midnight_and_closed_days(): void
    {
        $service = app(StoreStatusService::class);
        $settings = $this->settings(['open' => '16:00', 'close' => '01:00', 'closed' => false]);

        $this->assertTrue($service->isWithinOperatingHours($settings, Carbon::parse('2026-11-16 23:30')));
        $this->assertTrue($service->isWithinOperatingHours($settings, Carbon::parse('2026-11-16 00:30')));
        $this->assertFalse($service->isWithinOperatingHours($settings, Carbon::parse('2026-11-16 12:00')));

        $closed = $this->settings(['open' => '10:00', 'close' => '22:00', 'closed' => true]);
        $this->assertFalse($service->isWithinOperatingHours($closed, Carbon::parse('2026-11-16 12:00')));
    }

    public function test_auto_schedule_can_be_disabled(): void
    {
        $settings = $this->settings(['open' => '10:00', 'close' => '22:00', 'closed' => false]);
        $settings->update(['auto_schedule' => false]);

        $this->assertFalse(app(StoreStatusService::class)->syncWithSchedule(Carbon::parse('2026-11-16 12:00')));
        $this->assertFalse(StoreSetting::current()->is_open);
    }
}

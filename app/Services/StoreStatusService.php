<?php

namespace App\Services;

use App\Models\StoreSetting;
use Carbon\CarbonInterface;

/**
 * Buka/tutup otomatis sesuai jadwal. Override manual tetap berlaku
 * sampai batas jadwal berikutnya (jam buka/tutup berikutnya).
 */
class StoreStatusService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function isWithinOperatingHours(StoreSetting $settings, CarbonInterface $now): bool
    {
        $day = strtolower($now->englishDayOfWeek);
        $hours = $settings->operating_hours[$day] ?? null;
        if (! $hours || ! empty($hours['closed'])) {
            return false;
        }

        $time = $now->format('H:i');
        $open = $hours['open'];
        $close = $hours['close'];

        // Mendukung jam tutup lewat tengah malam, misal 16:00–01:00
        return $open <= $close
            ? $time >= $open && $time < $close
            : $time >= $open || $time < $close;
    }

    /** Dipanggil scheduler tiap menit. Mengembalikan true jika status berubah. */
    public function syncWithSchedule(CarbonInterface $now): bool
    {
        $settings = StoreSetting::current();
        if (! $settings->auto_schedule) {
            return false;
        }

        $scheduled = $this->isWithinOperatingHours($settings, $now);
        if ($settings->last_schedule_state === $scheduled) {
            return false;
        }

        $changed = $settings->is_open !== $scheduled;
        $settings->forceFill(['last_schedule_state' => $scheduled, 'is_open' => $scheduled])->save();

        if ($changed) {
            $this->logger->log('auto_'.($scheduled ? 'open' : 'close'), $settings, null, ['is_open' => $scheduled], 'Jadwal otomatis');
        }

        return $changed;
    }

    public function setOpen(bool $isOpen): StoreSetting
    {
        $settings = StoreSetting::current();
        $before = ['is_open' => $settings->is_open];
        $settings->forceFill(['is_open' => $isOpen])->save();
        $this->logger->log($isOpen ? 'open' : 'close', $settings, $before, ['is_open' => $isOpen], 'Buka/tutup manual');

        return $settings;
    }
}

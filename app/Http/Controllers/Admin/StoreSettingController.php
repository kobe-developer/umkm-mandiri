<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSettingRequest;
use App\Models\StoreSetting;
use App\Services\ActivityLogger;
use App\Services\BrandIconService;
use App\Services\StoreStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreSettingController extends Controller
{
    public function edit(StoreStatusService $status, BrandIconService $icons): Response
    {
        $settings = StoreSetting::current();

        return Inertia::render('Admin/Store', [
            'settings' => $settings,
            'days' => StoreSetting::DAYS,
            'withinHours' => $status->isWithinOperatingHours($settings, now()),
            'menuUrl' => route('menu.index'),
            'brandIcons' => $icons->urls($settings),
        ]);
    }

    public function update(StoreSettingRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $settings = StoreSetting::current();
        $original = $settings->getAttributes();
        $data = $request->validated();
        $settings->update([
            ...$data,
            'operating_hours' => collect(StoreSetting::DAYS)->mapWithKeys(fn ($day) => [$day => [
                'open' => $data['operating_hours'][$day]['open'],
                'close' => $data['operating_hours'][$day]['close'],
                'closed' => (bool) ($data['operating_hours'][$day]['closed'] ?? false),
            ]])->all(),
        ]);
        $logger->logChanges('update', $settings, $original, 'Ubah pengaturan toko');

        return back()->with('success', 'Pengaturan toko disimpan.');
    }

    /** Tombol Buka/Tutup manual. Berlaku sampai batas jadwal berikutnya. */
    public function toggle(Request $request, StoreStatusService $status): RedirectResponse
    {
        $data = $request->validate(['is_open' => ['required', 'boolean']]);
        $status->setOpen($data['is_open']);

        return back()->with('success', $data['is_open'] ? 'Toko dibuka.' : 'Toko ditutup. Menu online tetap tampil, checkout dinonaktifkan.');
    }
}

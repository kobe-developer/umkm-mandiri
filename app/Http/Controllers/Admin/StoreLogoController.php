<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Services\ActivityLogger;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Upload / hapus logo toko (dipakai untuk favicon, ikon PWA & notifikasi). */
class StoreLogoController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly ActivityLogger $logger,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=128,min_height=128'],
        ], [
            'logo.dimensions' => 'Logo minimal 128×128 piksel.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'logo.mimes' => 'Logo harus JPG, PNG, atau WebP.',
        ]);

        $settings = StoreSetting::current();
        $previous = $settings->logo_path;
        $settings->update(['logo_path' => $this->images->storeCompressed($request->file('logo'), 'branding')]);
        $this->images->delete($previous);
        $this->logger->log('update', $settings, ['logo_path' => $previous], ['logo_path' => $settings->logo_path], 'Ganti logo toko');

        return back()->with('success', 'Logo toko diperbarui.');
    }

    public function destroy(): RedirectResponse
    {
        $settings = StoreSetting::current();
        $previous = $settings->logo_path;
        $settings->update(['logo_path' => null]);
        $this->images->delete($previous);
        $this->logger->log('update', $settings, ['logo_path' => $previous], ['logo_path' => null], 'Hapus logo toko');

        return back()->with('success', 'Logo dihapus. Ikon memakai inisial nama toko.');
    }
}

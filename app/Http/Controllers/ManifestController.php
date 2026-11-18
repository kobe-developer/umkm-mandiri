<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use App\Services\BrandIconService;
use Illuminate\Http\JsonResponse;

/**
 * Web app manifest (PWA). Dibutuhkan agar situs bisa dipasang ke layar utama — di iPhone
 * web push hanya berjalan dari aplikasi yang sudah dipasang. Ada dua varian: menu pelanggan
 * dan POS staf, beda halaman awal.
 */
class ManifestController extends Controller
{
    public function __construct(private readonly BrandIconService $icons) {}

    private const THEME_COLOR = '#b93a1a';

    private const BACKGROUND_COLOR = '#f6f2ea';

    public function menu(): JsonResponse
    {
        $name = StoreSetting::current()->store_name;

        return $this->manifest(id: '/menu', name: $name, shortName: $name, startUrl: '/menu');
    }

    public function pos(): JsonResponse
    {
        $name = StoreSetting::current()->store_name;

        return $this->manifest(id: '/pos', name: "{$name} · Kasir", shortName: 'Kasir', startUrl: '/pos');
    }

    private function manifest(string $id, string $name, string $shortName, string $startUrl): JsonResponse
    {
        return response()->json([
            'id' => $id,
            'name' => $name,
            'short_name' => rtrim(mb_substr($shortName, 0, 12)),
            'lang' => 'id',
            'start_url' => $startUrl,
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => self::BACKGROUND_COLOR,
            'theme_color' => self::THEME_COLOR,
            'icons' => [
                ['src' => $this->icons->url('icon-192'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $this->icons->url('icon-512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $this->icons->url('icon-512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'no-cache'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}

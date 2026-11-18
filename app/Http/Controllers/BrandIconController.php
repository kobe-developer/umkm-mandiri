<?php

namespace App\Http\Controllers;

use App\Services\BrandIconService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Ikon toko dinamis: /brand/{variant}.png?v={versi}. */
class BrandIconController extends Controller
{
    private const IMMUTABLE_SECONDS = 31536000;

    private const STALE_SECONDS = 300;

    public function __invoke(Request $request, BrandIconService $icons, string $variant): Response
    {
        // URL ber-versi terbaru boleh di-cache selamanya; versi lama/tanpa versi hanya sebentar
        $isCurrent = $request->query('v') === $icons->version();
        $cache = $isCurrent ? 'public, max-age='.self::IMMUTABLE_SECONDS.', immutable' : 'public, max-age='.self::STALE_SECONDS;

        return response($icons->png($variant), 200, ['Content-Type' => 'image/png', 'Cache-Control' => $cache]);
    }
}

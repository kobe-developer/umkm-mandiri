<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

/**
 * Key Reverb dikirim dari server saat runtime, bukan di-bake ke bundle Vite:
 * image produksi dibangun tanpa .env, sehingga VITE_REVERB_APP_KEY kosong saat build
 * dan browser diam-diam jatuh ke polling.
 */
class RealtimeConfigTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    public function test_reverb_key_is_shared_when_realtime_is_enabled(): void
    {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'public-app-key']);

        $this->actingAs($this->owner())->get('/akun')->assertInertia(fn ($page) => $page
            ->where('config.realtime', true)
            ->where('config.reverbKey', 'public-app-key'));

        // Halaman publik (status pesanan pelanggan) juga memakai WebSocket
        auth()->logout();
        $this->get('/menu')->assertInertia(fn ($page) => $page->where('config.reverbKey', 'public-app-key'));
    }

    public function test_reverb_key_is_not_shared_when_realtime_is_disabled(): void
    {
        config(['broadcasting.default' => 'log', 'broadcasting.connections.reverb.key' => 'public-app-key']);

        $this->get('/menu')->assertInertia(fn ($page) => $page
            ->where('config.realtime', false)
            ->where('config.reverbKey', null));
    }
}

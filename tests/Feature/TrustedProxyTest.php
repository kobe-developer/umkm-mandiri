<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    private const HTTPS_FORWARD = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'];

    protected function tearDown(): void
    {
        $this->setTrustedProxiesEnv('');
        parent::tearDown();
    }

    public function test_request_from_configured_proxy_is_treated_as_https(): void
    {
        $this->bootWithTrustedProxies('127.0.0.1');

        $this->withHeaders(self::HTTPS_FORWARD)->get('/_probe')
            ->assertOk()
            ->assertJson(['secure' => true, 'asset_scheme' => 'https']);
    }

    public function test_forwarded_headers_are_ignored_when_no_proxy_is_configured(): void
    {
        $this->bootWithTrustedProxies('');

        $this->withHeaders(self::HTTPS_FORWARD)->get('/_probe')
            ->assertOk()
            ->assertJson(['secure' => false, 'asset_scheme' => 'http']);
    }

    public function test_trusted_proxies_are_read_from_config(): void
    {
        $this->bootWithTrustedProxies('10.0.0.0/8, 172.16.0.0/12');

        $this->assertSame(['10.0.0.0/8', '172.16.0.0/12'], config('app.trusted_proxies'));
    }

    private function bootWithTrustedProxies(string $value): void
    {
        TrustProxies::flushState();
        $this->setTrustedProxiesEnv($value);
        $this->refreshApplication();

        Route::get('/_probe', fn (Request $request) => [
            'secure' => $request->isSecure(),
            'asset_scheme' => parse_url(asset('build/app.css'), PHP_URL_SCHEME),
        ]);
    }

    private function setTrustedProxiesEnv(string $value): void
    {
        putenv("TRUSTED_PROXIES={$value}");
        $_ENV['TRUSTED_PROXIES'] = $value;
        $_SERVER['TRUSTED_PROXIES'] = $value;
    }
}

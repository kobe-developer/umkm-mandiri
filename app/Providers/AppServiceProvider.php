<?php

namespace App\Providers;

use App\Models\StoreSetting;
use App\Services\BrandIconService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $this->configureTrustedProxies();
        $this->configureRateLimiting();
        $this->shareBrandWithRootView();
    }

    /** Judul awal, favicon & ikon iOS di <head> mengikuti profil toko. */
    private function shareBrandWithRootView(): void
    {
        View::composer('app', function ($view) {
            $settings = StoreSetting::current();
            $icons = $this->app->make(BrandIconService::class);

            $view->with('brand', [
                'name' => $settings->store_name,
                'favicon' => $icons->url('favicon', $settings),
                'apple_touch' => $icons->url('apple-touch', $settings),
            ]);
        });
    }

    /**
     * Dibaca dari config (bukan env() di bootstrap/app.php) karena callback middleware
     * di sana dijalankan sebelum .env dimuat, sehingga nilainya selalu kosong.
     */
    private function configureTrustedProxies(): void
    {
        $proxies = config('app.trusted_proxies', []);
        if ($proxies === []) {
            return;
        }

        TrustProxies::at($proxies === ['*'] ? '*' : $proxies);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(config('store.checkout_rate_limit_per_minute'))
            ->by($request->ip())
            ->response(fn () => back()->withErrors(['store' => 'Terlalu banyak percobaan. Coba lagi sebentar lagi.'])));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('username')).'|'.$request->ip()));
    }
}

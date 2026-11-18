<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Notifications\PushMessage;
use App\Services\BrandIconService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class StoreBrandingTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->setUpShop();
    }

    private function icons(): BrandIconService
    {
        return app(BrandIconService::class);
    }

    /** @return array{0: int, 1: int} */
    private function pngSize(string $png): array
    {
        $image = imagecreatefromstring($png);

        return [imagesx($image), imagesy($image)];
    }

    private function alphaAt(string $png, int $x, int $y): int
    {
        $image = imagecreatefromstring($png);

        return imagecolorsforindex($image, imagecolorat($image, $x, $y))['alpha'];
    }

    public function test_every_icon_variant_is_a_png_of_the_right_size(): void
    {
        foreach (BrandIconService::VARIANTS as $variant => ['size' => $size]) {
            $response = $this->get($this->icons()->url($variant))->assertOk()->assertHeader('Content-Type', 'image/png');

            $this->assertSame([$size, $size], $this->pngSize($response->getContent()), $variant);
            $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
        }
    }

    public function test_circle_icons_are_transparent_outside_and_badge_is_knocked_out(): void
    {
        $notification = $this->icons()->png('notification');
        $this->assertSame(127, $this->alphaAt($notification, 0, 0), 'sudut lingkaran transparan');
        $this->assertSame(0, $this->alphaAt($notification, 20, 96), 'isi lingkaran opaque');

        $square = $this->icons()->png('icon-512');
        $this->assertSame(0, $this->alphaAt($square, 0, 0), 'ikon PWA full-bleed (maskable)');

        $badge = $this->icons()->png('badge');
        $this->assertSame(127, $this->alphaAt($badge, 0, 0));
    }

    public function test_unknown_variant_is_not_found_and_old_version_is_cached_briefly(): void
    {
        $this->get('/brand/hack.png')->assertNotFound();

        $response = $this->get('/brand/favicon.png?v=lama')->assertOk();
        $this->assertStringNotContainsString('immutable', $response->headers->get('Cache-Control'));
    }

    public function test_icon_version_follows_initials_and_logo(): void
    {
        $this->settings->update(['store_name' => 'Ayam Penyet Jomblo']);
        $version = $this->icons()->version();

        $this->settings->update(['store_name' => 'Ayam Panggang']);
        $this->assertSame($version, $this->icons()->version(), 'inisial tetap AP → ikon sama');

        $this->settings->update(['store_name' => 'Juciuecy']);
        $this->assertNotSame($version, $this->icons()->version());
    }

    public function test_owner_uploads_and_removes_logo(): void
    {
        $owner = $this->owner();
        $monogram = $this->icons()->png('notification');

        $this->actingAs($owner)
            ->post('/admin/toko/logo', ['logo' => UploadedFile::fake()->image('logo.png', 300, 300)])
            ->assertSessionHasNoErrors();

        $settings = StoreSetting::current();
        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);
        $this->assertNotSame($monogram, $this->icons()->png('notification'), 'ikon memakai logo');

        $path = $settings->logo_path;
        $this->delete('/admin/toko/logo')->assertSessionHasNoErrors();
        $this->assertNull(StoreSetting::current()->logo_path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame($monogram, $this->icons()->png('notification'), 'kembali ke monogram');
    }

    public function test_logo_upload_is_validated_and_owner_only(): void
    {
        $this->actingAs($this->cashier())
            ->post('/admin/toko/logo', ['logo' => UploadedFile::fake()->image('logo.png', 300, 300)])
            ->assertForbidden();

        $this->actingAs($this->owner())
            ->post('/admin/toko/logo', ['logo' => UploadedFile::fake()->image('kecil.png', 64, 64)])
            ->assertSessionHasErrors('logo');
        $this->post('/admin/toko/logo', ['logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('logo');

        $this->assertNull(StoreSetting::current()->logo_path);
    }

    public function test_head_manifest_and_props_use_store_branding(): void
    {
        $this->settings->update(['store_name' => 'Juciuecy']);
        $favicon = $this->icons()->url('favicon');

        $this->get('/menu')
            ->assertSee('<title inertia>Juciuecy</title>', false)
            ->assertSee($favicon, false)
            ->assertSee($this->icons()->url('apple-touch'), false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('store.name', 'Juciuecy')
                ->where('store.initials', 'JY')
                ->where('store.favicon_url', $favicon)
                ->where('store.logo_url', null));

        $this->get('/manifest.webmanifest')->assertJsonPath('icons.0.src', $this->icons()->url('icon-192'));
        $this->get('/favicon.ico')->assertRedirect($favicon);
    }

    public function test_push_messages_use_store_icons(): void
    {
        $message = PushMessage::make('Judul', 'Isi', '/menu', 'tag')->toArray();

        $this->assertSame($this->icons()->url('notification'), $message['icon']);
        $this->assertSame($this->icons()->url('badge'), $message['badge']);
    }
}

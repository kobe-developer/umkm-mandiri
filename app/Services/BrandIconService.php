<?php

namespace App\Services;

use App\Models\StoreSetting;
use App\Support\StoreInitials;
use GdImage;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Ikon toko yang dinamis (favicon, ikon PWA, ikon & badge notifikasi), digambar dengan GD:
 * - bila toko punya logo → logo di atas latar terang;
 * - bila belum → monogram inisial toko (lihat StoreInitials) di atas warna brand.
 * Hasil di-cache di disk per "versi" (inisial + logo), URL memuat versi agar browser
 * langsung memakai ikon baru setelah profil toko diubah.
 */
class BrandIconService
{
    /** Naikkan bila desain ikon berubah agar cache lama tidak dipakai. */
    private const RENDER_VERSION = 1;

    /** @var array<string, array{size: int, shape: string}> */
    public const VARIANTS = [
        'favicon' => ['size' => 64, 'shape' => 'circle'],
        'notification' => ['size' => 192, 'shape' => 'circle'],
        'badge' => ['size' => 96, 'shape' => 'badge'],
        'icon-192' => ['size' => 192, 'shape' => 'square'],
        'icon-512' => ['size' => 512, 'shape' => 'square'],
        'apple-touch' => ['size' => 180, 'shape' => 'square'],
    ];

    private const BRAND = [185, 58, 26];

    private const LOGO_BACKGROUND = [255, 253, 249];

    private const WHITE = [255, 255, 255];

    private const SUPERSAMPLE = 4;

    /** Ukuran kotak logo relatif kanvas: ikon PWA maskable harus muat di zona aman (lingkaran 80%). */
    private const LOGO_RATIO = ['square' => 0.56, 'circle' => 0.64];

    /** Lebar & tinggi maksimum teks monogram relatif kanvas. */
    private const TEXT_MAX_WIDTH = 0.58;

    private const TEXT_MAX_HEIGHT = 0.34;

    private const CACHE_DIR = 'brand-icons';

    private const FONT = 'vendor/dompdf/dompdf/lib/fonts/DejaVuSerif-Bold.ttf';

    public function version(?StoreSetting $settings = null): string
    {
        $settings ??= StoreSetting::current();

        return substr(sha1(self::RENDER_VERSION.'|'.StoreInitials::from($settings->store_name).'|'.$settings->logo_path), 0, 10);
    }

    /** URL relatif ber-versi, aman untuk host mana pun (localhost, ngrok, domain). */
    public function url(string $variant, ?StoreSetting $settings = null): string
    {
        $this->assertVariant($variant);

        return route('brand.icon', ['variant' => $variant, 'v' => $this->version($settings)], absolute: false);
    }

    /** @return array<string, string> URL semua varian, untuk props frontend/blade */
    public function urls(?StoreSetting $settings = null): array
    {
        $settings ??= StoreSetting::current();

        return collect(array_keys(self::VARIANTS))->mapWithKeys(fn ($v) => [$v => $this->url($v, $settings)])->all();
    }

    /** PNG ikon (dari cache bila ada). */
    public function png(string $variant): string
    {
        $this->assertVariant($variant);
        $settings = StoreSetting::current();
        $path = self::CACHE_DIR.'/'.$this->version($settings)."/{$variant}.png";
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            $disk->put($path, $this->render($variant, $settings));
        }

        return $disk->get($path);
    }

    public function flush(): void
    {
        Storage::disk('local')->deleteDirectory(self::CACHE_DIR);
    }

    private function render(string $variant, StoreSetting $settings): string
    {
        ['size' => $size, 'shape' => $shape] = self::VARIANTS[$variant];
        $big = $size * self::SUPERSAMPLE;
        $canvas = $this->transparentCanvas($big);
        $logo = $shape === 'badge' ? null : $this->loadLogo($settings);
        $initials = StoreInitials::from($settings->store_name);

        match ($shape) {
            // Android memakai badge hanya sebagai siluet (alpha), jadi selalu monogram berlubang
            'badge' => $this->drawMonogram($canvas, $big, $initials, self::WHITE, null, circle: true, knockout: true),
            default => $logo
                ? $this->drawLogo($canvas, $big, $logo, circle: $shape === 'circle')
                : $this->drawMonogram($canvas, $big, $initials, self::BRAND, self::WHITE, circle: $shape === 'circle', knockout: false),
        };

        $output = $this->transparentCanvas($size);
        imagecopyresampled($output, $canvas, 0, 0, 0, 0, $size, $size, $big, $big);

        ob_start();
        imagepng($output, null, 9);

        return (string) ob_get_clean();
    }

    /** @param  array{0: int, 1: int, 2: int}  $background */
    private function drawMonogram(GdImage $canvas, int $big, string $initials, array $background, ?array $textColor, bool $circle, bool $knockout): void
    {
        $this->fillShape($canvas, $big, $background, $circle);

        $font = base_path(self::FONT);
        $fontSize = $big * 0.4;
        $box = imagettfbbox($fontSize, 0, $font, $initials);
        $scale = min(($big * self::TEXT_MAX_WIDTH) / ($box[2] - $box[0]), ($big * self::TEXT_MAX_HEIGHT) / ($box[1] - $box[7]));
        $fontSize *= $scale;
        $box = imagettfbbox($fontSize, 0, $font, $initials);
        $x = (int) round(($big - ($box[2] - $box[0])) / 2 - $box[0]);
        $y = (int) round(($big + ($box[1] - $box[7])) / 2 - $box[1]);

        if ($knockout) {
            imagealphablending($canvas, false); // huruf dibuat transparan (berlubang)
            $color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        } else {
            $color = imagecolorallocate($canvas, ...$textColor);
        }
        imagettftext($canvas, $fontSize, 0, $x, $y, $color, $font, $initials);
        imagealphablending($canvas, true);
    }

    private function drawLogo(GdImage $canvas, int $big, GdImage $logo, bool $circle): void
    {
        $this->fillShape($canvas, $big, self::LOGO_BACKGROUND, $circle);

        $box = $big * self::LOGO_RATIO[$circle ? 'circle' : 'square'];
        $width = imagesx($logo);
        $height = imagesy($logo);
        $scale = min($box / $width, $box / $height);
        $targetWidth = (int) round($width * $scale);
        $targetHeight = (int) round($height * $scale);

        imagecopyresampled($canvas, $logo, (int) (($big - $targetWidth) / 2), (int) (($big - $targetHeight) / 2), 0, 0, $targetWidth, $targetHeight, $width, $height);
    }

    /** @param  array{0: int, 1: int, 2: int}  $color */
    private function fillShape(GdImage $canvas, int $big, array $color, bool $circle): void
    {
        $fill = imagecolorallocate($canvas, ...$color);
        if ($circle) {
            imagefilledellipse($canvas, intdiv($big, 2), intdiv($big, 2), $big - 2, $big - 2, $fill);
        } else {
            imagefilledrectangle($canvas, 0, 0, $big - 1, $big - 1, $fill);
        }
    }

    private function loadLogo(StoreSetting $settings): ?GdImage
    {
        if (! $settings->logo_path || ! Storage::disk('public')->exists($settings->logo_path)) {
            return null;
        }

        $image = @imagecreatefromstring(Storage::disk('public')->get($settings->logo_path));
        if (! $image instanceof GdImage) {
            report(new InvalidArgumentException("Logo toko tidak bisa dibaca: {$settings->logo_path}"));

            return null;
        }
        imagepalettetotruecolor($image);
        imagesavealpha($image, true);

        return $image;
    }

    private function transparentCanvas(int $size): GdImage
    {
        $image = imagecreatetruecolor($size, $size);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagealphablending($image, true);

        return $image;
    }

    private function assertVariant(string $variant): void
    {
        if (! array_key_exists($variant, self::VARIANTS)) {
            throw new InvalidArgumentException("Varian ikon tidak dikenal: {$variant}");
        }
    }
}

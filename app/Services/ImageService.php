<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

/** Simpan foto ke disk public dengan kompresi (agar menu online cepat di 4G). */
class ImageService
{
    public function storeCompressed(UploadedFile $file, string $directory): string
    {
        $max = config('store.photo_max_dimension');
        $encoded = Image::decodePath($file->getRealPath())
            ->scaleDown($max, $max)
            ->encode(new WebpEncoder(quality: config('store.photo_quality')));

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $encoded->toString());

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}

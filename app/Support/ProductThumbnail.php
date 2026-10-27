<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Ro'yxat kartalari uchun kichik nusxa (asl rasm 1280px, kartada ~104px ko'rinadi).
 * Rasm qirqilmaydi, faqat kichraytiriladi: crop (crop_x/y/scale) brauzerda xuddi asl rasmdagidek ishlaydi.
 */
class ProductThumbnail
{
    // Retina ekranda 104px × 3 ≈ 320px — undan kattasi kartaga keraksiz trafik
    private const SIZE = 360;

    /**
     * @param string $src asl rasm yo'li ("products/uuid.webp")
     * @return string|null kichik nusxa yo'li ("products/thumbs/uuid.webp"); yasab bo'lmasa null — karta asl rasmni ko'rsatadi
     */
    public static function make(string $src): ?string
    {
        $source = public_path('storage/' . ltrim($src, '/'));
        if (!File::exists($source)) {
            return null;
        }

        $path = 'products/thumbs/' . pathinfo($src, PATHINFO_FILENAME) . '.webp';
        $destination = public_path('storage/' . $path);

        try {
            File::ensureDirectoryExists(dirname($destination));

            ImageManager::usingDriver(Driver::class)
                ->decodePath($source)
                ->scaleDown(self::SIZE, self::SIZE)
                ->encode(new WebpEncoder(quality: 75, strip: true))
                ->save($destination);

            return $path;
        } catch (\Throwable $e) {
            Log::warning("Kichik rasm yasalmadi: {$src}: " . $e->getMessage());

            return null;
        }
    }
}

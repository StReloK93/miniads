<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/** Vitrina rasmlari (logo, muqova, mahsulot): webp'ga o'giriladi va kichraytiriladi, public/storage ostida */
class VitrinaImage
{
    public const LOGO = 400;
    public const COVER = 1280;
    public const ITEM = 1024;

    /** @return string "vitrinas/<dir>/uuid.webp" — /storage/ ga nisbatan yo'l */
    public static function store(UploadedFile $file, string $dir, int $maxSize): string
    {
        if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \InvalidArgumentException(__('messages.image_invalid'));
        }

        $path = "vitrinas/{$dir}/" . Str::uuid() . '.webp';
        $destination = public_path('storage/' . $path);
        File::ensureDirectoryExists(dirname($destination));

        ImageManager::usingDriver(Driver::class)
            ->decode($file->get())
            ->scaleDown($maxSize, $maxSize)
            ->encode(new WebpEncoder(quality: 82, strip: true))
            ->save($destination);

        return $path;
    }

    public static function delete(?string $path): void
    {
        // Faqat vitrina papkasidagi fayllar: boshqa yo'l (masalan "../") o'chirilmaydi
        if (!$path || !str_starts_with($path, 'vitrinas/') || str_contains($path, '..')) {
            return;
        }

        File::delete(public_path('storage/' . $path));
    }
}

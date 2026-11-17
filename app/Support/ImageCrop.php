<?php

namespace App\Support;

/**
 * Rasmning kartadagi ko'rinishi (FieldImage → ProductImageView): markaz nuqtasi (%) va yaqinlashtirish.
 * Rasm faylining o'zi qirqilmaydi — brauzer shu qiymatlar bo'yicha ko'rsatadi. E'lon va vitrina rasmlarida bir xil.
 */
class ImageCrop
{
    /** @return array{crop_x: int, crop_y: int, crop_scale: float} */
    public static function normalize(array $crop): array
    {
        return [
            'crop_x' => max(0, min(100, (int) ($crop['crop_x'] ?? 50))),
            'crop_y' => max(0, min(100, (int) ($crop['crop_y'] ?? 50))),
            'crop_scale' => max(1, min(3, (float) ($crop['crop_scale'] ?? 1))),
        ];
    }

    /** Tekshiruv qoidalari: "images.*" kabi prefiks bilan */
    public static function rules(string $prefix): array
    {
        return [
            "{$prefix}.crop_x" => ['nullable', 'numeric'],
            "{$prefix}.crop_y" => ['nullable', 'numeric'],
            "{$prefix}.crop_scale" => ['nullable', 'numeric'],
        ];
    }
}

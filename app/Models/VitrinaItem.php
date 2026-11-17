<?php

namespace App\Models;

use App\Support\ImageCrop;
use App\Support\VitrinaImage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class VitrinaItem extends Model
{
    /** fixed — aniq narx, from — "...dan boshlab", negotiable — kelishiladi, none — narx ko'rsatilmaydi */
    public const PRICE_MODES = ['fixed', 'from', 'negotiable', 'none'];

    public const MAX_IMAGES = 6;

    protected $fillable = [
        'vitrina_section_id', 'title', 'description', 'price_mode', 'price',
        'price_type_id', 'unit', 'is_available', 'position',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'integer',
    ];

    protected $with = ['price_type'];

    /**
     * Rasmlar: [{src, crop_x, crop_y, crop_scale}, ...] — e'lon rasmlaridagidek crop bilan.
     * Eski yozuvlar oddiy yo'l ("vitrinas/items/a.webp") bo'lib saqlangan — o'qishda markazlangan crop beriladi.
     */
    protected function images(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : array_values(array_map(
                fn ($image) => is_string($image)
                    ? ['src' => $image, ...ImageCrop::normalize([])]
                    : ['src' => (string) $image['src'], ...ImageCrop::normalize($image)],
                json_decode($value, true) ?: [],
            )),
            set: fn (?array $value) => $value === null ? null : json_encode(array_values($value), JSON_UNESCAPED_SLASHES),
        );
    }

    public function vitrina()
    {
        return $this->belongsTo(Vitrina::class);
    }

    public function price_type()
    {
        return $this->belongsTo(PriceType::class);
    }

    /** Mahsulot rasmlari bilan birga o'chadi (disk to'lmasin) */
    public function purge(): void
    {
        foreach ($this->images ?? [] as $image) {
            VitrinaImage::delete($image['src']);
        }
        $this->delete();
    }
}

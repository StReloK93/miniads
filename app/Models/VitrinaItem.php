<?php

namespace App\Models;

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
        'images' => 'array',
        'is_available' => 'boolean',
        'price' => 'integer',
    ];

    protected $with = ['price_type'];

    public function vitrina()
    {
        return $this->belongsTo(Vitrina::class);
    }

    public function price_type()
    {
        return $this->belongsTo(PriceType::class);
    }
}

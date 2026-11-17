<?php

namespace App\Models;

use App\Support\VitrinaImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Biznesning doimiy sahifasi: do'kon, menyu, portfolio, narxnoma yoki ko'chmas mulk.
 * Admin tasdiqlamaguncha (status = approved) boshqalarga ko'rinmaydi.
 */
class Vitrina extends Model
{
    public const TYPES = ['shop', 'menu', 'portfolio', 'pricelist', 'realty'];

    /** Mahsulotlar ko'rinishi (null — turidan): grid — rasmli plitkalar, list — matnli ro'yxat */
    public const LAYOUTS = ['grid', 'list'];

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'name', 'type', 'layout', 'description', 'district_id', 'address',
        'phone', 'telegram', 'instagram', 'work_hours',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    protected $with = ['district'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function sections()
    {
        return $this->hasMany(VitrinaSection::class)->orderBy('position')->orderBy('id');
    }

    public function items()
    {
        return $this->hasMany(VitrinaItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Vitrinani butunlay o'chirish (egasi yoki admin): logo, muqova va barcha mahsulot rasmlari diskdan ham o'chadi.
     * Bo'limlar bazada cascade bilan o'chadi; vitrinaga bog'langan e'lonlar qoladi (vitrina_id → null).
     */
    public function purge(): void
    {
        $this->items()->get()->each->purge();
        VitrinaImage::delete($this->logo);
        VitrinaImage::delete($this->cover);
        $this->delete();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('vitrinas.status', self::APPROVED);
    }

    /** Lotincha, takrorlanmas havola nomi: "Mebel Lux" → "mebel-lux", band bo'lsa "mebel-lux-2" */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug(\App\Support\UzbekText::normalize($name)) ?: 'vitrina';
        $base = Str::limit($base, 40, '');
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}

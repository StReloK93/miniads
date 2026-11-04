<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model
{

    use Searchable, SoftDeletes;

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'user_id',
        'district_id',
        'phone',
        'price',
        'price_type_id',
        'views_count',
        'back_color_id',
        'expires_at',
        'is_bot',
        'source_channel',
        'source_message_id',
        'source_hash',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
    ];


    /** Muddatsiz e'lonlarning tugash sanasi: shundan keyingi har qanday sana "muddatsiz" hisoblanadi */
    public const PERMANENT_UNTIL = '2099-12-31 23:59:59';

    public static function permanentUntil(): Carbon
    {
        return Carbon::parse(self::PERMANENT_UNTIL);
    }

    public function isPermanent(): bool
    {
        return $this->expires_at && Carbon::parse($this->expires_at)->gte(self::permanentUntil());
    }

    public function getDaysAttribute()
    {
        if (!$this->expires_at)
            return null;

        $hours = now()->diffInDays($this->expires_at, false);

        return [
            'current' => round($hours, 2),
            'max' => $this->category->listing_duration_days,
            // Ilova "N kun qoldi" o'rniga "Muddatsiz" yozadi
            'permanent' => $this->isPermanent(),
        ];
    }

    // public function getMaxDaysAttribute()
    // {
    //     return $this->category->listing_duration_days;
    // }

    protected $with = ['parameter_values', 'images', 'price_type', 'district', 'backColor', 'category'];

    protected $hidden = [
        'expires_at',
        'expiry_reminded_for',
    ];

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('expires_at', '>=', now());
    }

    #[Scope]
    protected function passive(Builder $query): void
    {
        $query->where('expires_at', '<=', now());
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setDistrictIdAttribute($value): void
    {
        $this->attributes['district_id'] = (!empty($value) && (int) $value !== 0) ? (int) $value : null;
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function parameter_values()
    {
        return $this->hasMany(ProductParameterValue::class, 'product_id');
    }


    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function price_type()
    {
        return $this->belongsTo(PriceType::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports()
    {
        return $this->hasMany(ProductReport::class);
    }

    /** Admin hali ko'rib chiqmagan shikoyatlar */
    public function openReports()
    {
        return $this->hasMany(ProductReport::class)->whereNull('resolved_at');
    }


    public function backColor()
    {
        return $this->belongsTo(BackColor::class, 'back_color_id');
    }
}

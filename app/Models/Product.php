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
        // ISO formatda ketadi: ilova "N kun oldin" ni shundan hisoblaydi (xom "Y-m-d H:i:s" ni iOS Safari o'qimaydi)
        'published_at' => 'datetime',
        'sold_at' => 'datetime',
    ];


    /**
     * Muddatsiz e'lon: expires_at = null — hech qachon o'chmaydi (kategoriya "muddatsiz" yoki admin shunday qo'ygan).
     * Faol/arxiv so'rovlari active()/passive() scope'lari orqali — null'ni hisobga oladi.
     */
    public function isPermanent(): bool
    {
        // Ustun tanlanmagan bo'lsa (select'da yo'q) — muddatsiz deb hisoblamaymiz
        return array_key_exists('expires_at', $this->attributes) && $this->attributes['expires_at'] === null;
    }

    protected static function booted(): void
    {
        // expires_at umuman berilmagan yangi e'lon muddatsiz (null) bo'lib qolmasin — kategoriya muddatini oladi.
        // Muddatsiz qilish uchun expires_at => null ataylab beriladi
        static::creating(function (Product $product): void {
            if (! array_key_exists('expires_at', $product->getAttributes())) {
                $category = $product->category_id ? Category::find($product->category_id) : null;
                $product->expires_at = $category
                    ? $category->listingExpiresAt()
                    : now()->addDays(Category::DEFAULT_LISTING_DAYS);
            }
        });
    }

    /** Ilova "Muddatsiz e'lon" belgisini ko'rsatadi (expires_at yashirin) */
    protected $appends = ['is_permanent'];

    public function getIsPermanentAttribute(): bool
    {
        return $this->isPermanent();
    }

    public function getDaysAttribute()
    {
        if ($this->isPermanent()) {
            return ['current' => null, 'max' => null, 'permanent' => true];
        }

        $hours = now()->diffInDays($this->expires_at, false);

        return [
            'current' => round($hours, 2),
            // Admin kategoriyadagidan uzunroq muddat qo'ygan bo'lishi mumkin
            'max' => max((int) $this->category?->listing_duration_days, (int) ceil($hours)),
            'permanent' => false,
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
        $query->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    #[Scope]
    protected function passive(Builder $query): void
    {
        $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
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

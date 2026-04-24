<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
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
        'expires_at'
    ];


    public function getDaysAttribute()
    {
        if (!$this->expires_at)
            return null;

        $hours = now()->diffInDays($this->expires_at, false);

        return [
            'current' => round($hours, 2),
            'max' => $this->category->listing_duration_days,
        ];
    }

    // public function getMaxDaysAttribute()
    // {
    //     return $this->category->listing_duration_days;
    // }

    protected $with = ['parameter_values', 'images', 'price_type', 'district', 'backColor'];

    protected $hidden = [
        'expires_at',
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


    public function backColor()
    {
        return $this->belongsTo(BackColor::class, 'back_color_id');
    }
}

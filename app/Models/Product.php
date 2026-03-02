<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'user_id',
        'district',
        'phone',
        'price',
        'price_type_id',
        'views_count',
        'expires_at'
    ];


    protected $with = ['parameter_values', 'images', 'price_type'];

    protected $hidden = [
        'expires_at',
    ];

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
}

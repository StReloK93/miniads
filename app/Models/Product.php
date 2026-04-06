<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Builder;
class Product extends Model
{

    use Searchable;

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

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
}

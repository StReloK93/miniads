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
        'views_count',
        'expires_at'
    ];


    protected $with = ['parameter_values'];

    protected $hidden = [
        'expires_at',
    ];


    public function parameter_values()
    {
        return $this->hasMany(ProductParameterValue::class, 'product_id');
    }
}

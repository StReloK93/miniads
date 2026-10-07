<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'src',
        'crop_src',
        'crop_x',
        'crop_y',
        'crop_scale',
        'product_id',
    ];

    protected $casts = [
        'crop_x' => 'integer',
        'crop_y' => 'integer',
        'crop_scale' => 'float',
    ];
}

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
        'views_count',
        'expires_at'
    ];
}

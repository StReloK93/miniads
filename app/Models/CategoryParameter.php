<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryParameter extends Model
{

    public $timestamps = false;
    protected $fillable = [
        'category_id',
        'parameter_id',
        'is_required',
        'sort_index'
    ];
}

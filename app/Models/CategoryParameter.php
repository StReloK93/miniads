<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
class CategoryParameter extends Pivot
{

    public $timestamps = false;
    protected $fillable = [
        'category_id',
        'parameter_id',
        'is_required',
        'sort_order'
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];
}

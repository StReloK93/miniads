<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    public $timestamps = false;

    protected $fillable = ['placeholder', 'type', 'component', 'unit', 'options'];

    protected $casts = [
        'options' => 'array',
    ];
}

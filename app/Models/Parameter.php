<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    public $timestamps = false;

    protected $with = ['options'];
    protected $fillable = ['placeholder', 'type', 'unit'];

    public function options()
    {
        return $this->hasMany(ParameterOption::class);
    }
}

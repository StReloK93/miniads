<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParameterValue extends Model
{
    //

    public $timestamps = false;

    protected $with = ["parameter"];
    protected $fillable = ['value', 'product_id', 'parameter_id'];


    public function parameter()
    {
        return $this->belongsTo(Parameter::class, 'parameter_id');
    }
}

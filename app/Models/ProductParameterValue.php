<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParameterValue extends Model
{
    public $timestamps = false;

    protected $with = ["parameter"];
    protected $fillable = ['value', 'product_id', 'parameter_id'];

    // Tanlov qiymati foydalanuvchi tilida ("Avtomat" → "Автомат"); asl qiymat `value`da qoladi
    protected $appends = ['display_value'];

    public function getDisplayValueAttribute(): ?string
    {
        if ($this->value === null || ! $this->relationLoaded('parameter') || ! $this->parameter) {
            return $this->value;
        }

        return $this->parameter->optionLabel((string) $this->value);
    }

    public function parameter()
    {
        return $this->belongsTo(Parameter::class, 'parameter_id');
    }
}

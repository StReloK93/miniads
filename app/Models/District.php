<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasTranslations;

    protected array $translatable = ['name'];

    protected $fillable = [
        'name',
        'translations',
    ];


    public $timestamps = false;
}

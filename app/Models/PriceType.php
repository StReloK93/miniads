<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class PriceType extends Model
{
    use HasTranslations;

    // "So'm" → "сум" / "sum"; "$" hamma tilda bir xil
    protected array $translatable = ['type'];

    protected $fillable = [
        'name',
        'type',
        'position',
        'translations',
    ];
}

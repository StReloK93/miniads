<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTelegramId extends Model
{
    //
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'channel_id',
        'message_id',
    ];
}

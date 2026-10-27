<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    /** Bir foydalanuvchiga — spam va og'ir tekshiruvlardan himoya */
    public const MAX_PER_USER = 10;

    protected $fillable = ['user_id', 'query', 'filters', 'params', 'label', 'is_paused', 'last_product_id', 'last_notified_at'];

    protected $casts = [
        'filters' => 'array',
        'params' => 'array',
        'is_paused' => 'boolean',
        'last_notified_at' => 'datetime',
    ];

    protected $hidden = ['last_product_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

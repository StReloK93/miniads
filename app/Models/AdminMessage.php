<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Admin e'lon muallifiga yozgan ogohlantirish (warning) yoki tushuntirish (info) */
class AdminMessage extends Model
{
    public const TYPES = ['warning', 'info'];

    protected $fillable = ['user_id', 'product_id', 'sender_id', 'type', 'body', 'telegram_sent', 'read_at'];

    protected $casts = [
        'telegram_sent' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReport extends Model
{
    /** Ilovadagi shikoyat oynasi bilan bir xil ro'yxat */
    public const REASONS = [
        'fraud' => 'Firibgarlik',
        'sold' => 'Sotilgan / dolzarb emas',
        'wrong_category' => "Noto'g'ri bo'lim",
        'wrong_info' => "Noto'g'ri narx yoki ma'lumot",
        'spam' => 'Spam / takroriy',
        'prohibited' => 'Taqiqlangan mahsulot',
        'other' => 'Boshqa',
    ];

    protected $fillable = ['product_id', 'user_id', 'reason', 'comment', 'resolved_at'];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    protected $appends = ['reason_label'];

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

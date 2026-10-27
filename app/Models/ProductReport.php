<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReport extends Model
{
    /** Sabablar ro'yxati; nomlari tarjimada: lang/{uz,ru,en}/messages.php → reasons */
    public const REASONS = ['fraud', 'sold', 'wrong_category', 'wrong_info', 'spam', 'prohibited', 'other'];

    protected $fillable = ['product_id', 'user_id', 'reason', 'comment', 'resolved_at'];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    protected $appends = ['reason_label'];

    public function getReasonLabelAttribute(): string
    {
        return in_array($this->reason, self::REASONS, true) ? __("messages.reasons.{$this->reason}") : $this->reason;
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Foydalanuvchining bitta kanaldagi a'zoligi (ChannelMembershipService yozadi) */
class ChannelMember extends Model
{
    protected $fillable = ['user_id', 'chat_id', 'status', 'is_member', 'checked_at'];

    protected $casts = [
        'is_member' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

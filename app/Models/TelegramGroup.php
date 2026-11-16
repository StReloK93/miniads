<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Bot kanalga a'zolikni tekshiradigan guruh (GroupSubscriptionGate) */
class TelegramGroup extends Model
{
    protected $fillable = ['chat_id', 'title', 'added_by'];
}

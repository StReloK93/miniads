<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSeenPost extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'channel',
        'message_id',
        'status',
        'reason',
    ];

    public static function mark(string $channel, int $messageId, string $status, ?string $reason = null): void
    {
        self::query()->insertOrIgnore([
            'channel' => $channel,
            'message_id' => $messageId,
            'status' => $status,
            'reason' => $reason !== null ? mb_substr($reason, 0, 255) : null,
            'created_at' => now(),
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSetting extends Model
{
    protected $table = 'bot_settings';

    protected $fillable = [
        'is_enabled',
        'hourly_limit',
        'daily_limit',
        'channels',
        'telegram_post_enabled',
        'telegram_start_hour',
        'telegram_end_hour',
        'ai_provider',
        'ai_api_key',
        'last_run_at',
        'today_imported_count',
        'today_date',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'telegram_post_enabled' => 'boolean',
        'hourly_limit' => 'integer',
        'daily_limit' => 'integer',
        'telegram_start_hour' => 'integer',
        'telegram_end_hour' => 'integer',
        'today_imported_count' => 'integer',
        'last_run_at' => 'datetime',
        'today_date' => 'date',
    ];

    public static function current(): self
    {
        $setting = self::query()->first();

        if (!$setting) {
            $setting = self::create([
                'is_enabled' => false,
                'hourly_limit' => 7,
                'daily_limit' => 150,
                'channels' => "@navoiy_bozor\n@karmana_bozor",
                'telegram_post_enabled' => true,
                'telegram_start_hour' => 8,
                'telegram_end_hour' => 23,
                'ai_provider' => 'gemini',
                'today_imported_count' => 0,
                'today_date' => now()->toDateString(),
            ]);
        }

        // Reset today's count if new day
        if ($setting->today_date?->format('Y-m-d') !== now()->toDateString()) {
            $setting->update([
                'today_date' => now()->toDateString(),
                'today_imported_count' => 0,
            ]);
        }

        return $setting;
    }

    public function getChannelList(): array
    {
        if (empty($this->channels)) {
            return [];
        }

        $lines = preg_split('/[\r\n,]+/', $this->channels);
        $clean = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                // Remove @ or full url if user pasted t.me/channel
                $trimmed = preg_replace('#^https?://t\.me/#i', '', $trimmed);
                $trimmed = ltrim($trimmed, '@');
                if ($trimmed !== '') {
                    $clean[] = $trimmed;
                }
            }
        }

        return array_unique($clean);
    }
}

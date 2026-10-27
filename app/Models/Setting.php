<?php

namespace App\Models;

use App\Support\UzbekText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Oddiy kalit-qiymat sozlamalar (admin paneldan o'zgaradi). Har so'rovda bazaga bormaslik uchun keshlanadi.
 */
class Setting extends Model
{
    public const BANNED_WORDS = 'banned_words';

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever("setting:{$key}", fn () => static::find($key)?->value) ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    /** Taqiqlangan so'zlar: har qatorda bitta so'z yoki ibora, normallashtirilgan holda */
    public static function bannedWords(): array
    {
        return collect(preg_split('/\R/u', (string) static::getValue(self::BANNED_WORDS, '')))
            ->map(fn (string $word) => UzbekText::normalize($word))
            ->filter(fn (string $word) => mb_strlen($word) >= 2)
            ->unique()
            ->values()
            ->all();
    }
}

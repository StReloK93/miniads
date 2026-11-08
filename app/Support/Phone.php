<?php

namespace App\Support;

/**
 * O'zbek telefon raqamlari yagona ko'rinishda: 93-123-45-67 (+998 / 998 / 8 old qo'shimchasisiz).
 * Mini app formasi va profil ham shu ko'rinishda ishlaydi.
 */
class Phone
{
    /**
     * Matn ichidagi raqam: +998 90 123 45 67, 998901234567, (90) 123-45-67, 90 123 4567, 901234567.
     * Ortidan valyuta kelsa (narx) — tegilmaydi.
     */
    private const IN_TEXT = '/(?<![\d+])(?:(?:\+\s?)?998[\s.\-]*)?\(?([2-9]\d)\)?[\s.\-]?(\d{3})[\s.\-]?(\d{2})[\s.\-]?(\d{2})(?!\d)(?!\s*(?:\$|usd|dollar|so[\'’`ʻ]?m|sum|сум|сўм|ming|минг|mln|млн|y\.?e\b|у\.?е))/ui';

    /** Bitta qiymatni formatlaydi. "@nick" o'zgarmaydi; tanilmagan qiymat o'zicha qoladi. */
    public static function format(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, '@')) {
            return $value;
        }

        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) === 12 && str_starts_with($digits, '998')) {
            $digits = substr($digits, 3);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '8')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) !== 9) {
            return $value;
        }

        return substr($digits, 0, 2).'-'.substr($digits, 2, 3).'-'.substr($digits, 5, 2).'-'.substr($digits, 7, 2);
    }

    /** Matndagi (masalan e'lon tavsifidagi) barcha raqamlarni 93-123-45-67 ko'rinishiga keltiradi. */
    public static function formatInText(string $text): string
    {
        return preg_replace_callback(self::IN_TEXT, fn (array $m) => "{$m[1]}-{$m[2]}-{$m[3]}-{$m[4]}", $text) ?? $text;
    }
}

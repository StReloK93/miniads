<?php

namespace App\Support;

/**
 * Qidiruv uchun o'zbekcha matnni bitta ko'rinishga keltiradi:
 * kirill → lotin, barcha tutuq belgilari (ʻ ’ ‘ ` ʼ ') olib tashlanadi, kichik harf.
 * Natijada "O‘g‘il", "o'g'il", "ogil" va "Ўғил" — bir xil "ogil".
 */
class UzbekText
{
    private const CYRILLIC = [
        // Ko'p harfli moslar avval: "ш" → "sh", "ў" → "o'" (tutuq keyin olib tashlanadi)
        'ш' => 'sh', 'щ' => 'sh', 'ч' => 'ch', 'ё' => 'yo', 'ю' => 'yu', 'я' => 'ya', 'ц' => 'ts',
        'ў' => 'o', 'қ' => 'q', 'ғ' => 'g', 'ҳ' => 'h',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'j',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
        'х' => 'x', 'ы' => 'i', 'э' => 'e', 'ъ' => '', 'ь' => '',
    ];

    private const APOSTROPHES = ["'", 'ʻ', 'ʼ', '’', '‘', '`', '´'];

    public static function normalize(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $text = mb_strtolower($text);
        $text = strtr($text, self::CYRILLIC);
        $text = str_replace(self::APOSTROPHES, '', $text);

        // Qolgan belgilar (tinish, emoji) — bo'shliq: so'z chegaralari saqlanadi
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text);

        return trim($text);
    }
}

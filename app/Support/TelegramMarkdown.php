<?php

namespace App\Support;

/**
 * Admin yozadigan post matni (Telegram ilovasidagi kabi belgilar) → Telegram HTML (parse_mode=HTML).
 * MarkdownV2 ishlatilmaydi: unda har bir "." "-" "(" ni ekranlash kerak, oddiy matn osongina buziladi.
 *
 *   **qalin**   __qiya__   ~~o'chirilgan~~   ||spoiler||   `kod`   ```kod bloki```
 *   [matn](https://havola)   "> " bilan boshlangan qatorlar — iqtibos (5+ qator — yig'iladigan)
 *
 * Boshqa hamma narsa ekranlanadi: admin "<" yoki "&" yozsa ham post buzilmaydi.
 */
class TelegramMarkdown
{
    /** Shundan uzun iqtibos Telegram'da yig'ilgan holda ko'rinadi ("yana" bosiladi) */
    private const EXPANDABLE_QUOTE_LINES = 5;

    public static function toHtml(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        $slots = [];
        $keep = function (string $html) use (&$slots): string {
            $slots[] = $html;

            return "\u{E000}" . (count($slots) - 1) . "\u{E001}";
        };

        // 1) Kod — ichidagi belgilar formatlanmaydi
        $text = preg_replace_callback('/```(?:[a-z0-9_+-]*\n)?(.+?)```/s', fn ($m) => $keep('<pre>' . self::escape(trim($m[1], "\n")) . '</pre>'), $text);
        $text = preg_replace_callback('/`([^`\n]+)`/', fn ($m) => $keep('<code>' . self::escape($m[1]) . '</code>'), $text);

        // 2) Havolalar — manzildagi "__" qiya yozuvga aylanmasin
        $text = preg_replace_callback(
            '/\[([^\]\n]+)\]\(((?:https?|tg):\/\/[^\s)]+)\)/i',
            fn ($m) => $keep('<a href="' . self::escape($m[2], true) . '">') . $m[1] . $keep('</a>'),
            $text,
        );

        // 3) Qolgan matn ekranlanadi, keyin belgilar teglarga aylanadi
        $html = self::escape($text);
        foreach ([
            '/\*\*(.+?)\*\*/s' => 'b',
            '/__(.+?)__/s' => 'i',
            '/~~(.+?)~~/s' => 's',
            '/\|\|(.+?)\|\|/s' => 'tg-spoiler',
        ] as $pattern => $tag) {
            $html = preg_replace($pattern, "<{$tag}>$1</{$tag}>", $html);
        }

        // 4) Iqtibos: ketma-ket "> " qatorlar bitta blok
        $html = preg_replace_callback('/(?:^&gt; ?.*(?:\n|$))+/m', function ($m) {
            $lines = preg_replace('/^&gt; ?/m', '', rtrim($m[0], "\n"));
            $open = substr_count($lines, "\n") + 1 >= self::EXPANDABLE_QUOTE_LINES ? '<blockquote expandable>' : '<blockquote>';

            return $open . $lines . "</blockquote>\n";
        }, $html);

        $html = rtrim($html, "\n");

        return preg_replace_callback("/\u{E000}(\d+)\u{E001}/u", fn ($m) => $slots[(int) $m[1]], $html);
    }

    /** Telegram'da ko'rinadigan matn uzunligi (teglarsiz) — 4096 / 1024 cheklovi shunga qo'llanadi */
    public static function visibleLength(string $html): int
    {
        return mb_strlen(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function escape(string $value, bool $attribute = false): string
    {
        $value = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $value);

        return $attribute ? str_replace('"', '&quot;', $value) : $value;
    }
}

<?php

namespace App\Services\Bot;

/**
 * AI'ga yuborishdan oldin bepul (regex) tekshiruv: e'lon bo'lishi aniq emas postlarni shu yerning o'zida to'xtatadi.
 * Qoidalar ataylab ehtiyotkor — shubhali postlar AI'ga o'tadi, faqat aniq e'lon bo'lmaganlar rad etiladi.
 */
class AdPrefilter
{
    // Sotish / olish / ijara / ish / xizmat niyatini bildiruvchi so'zlar (lotinlashtirilgan, kichik harfda)
    private const OFFER_PATTERNS = [
        '/\bsotil(?:adi|yapti|moqda|di)\b/u',
        '/\bsota(?:man|miz)\b/u',
        '/\bsotuvda\b/u',
        '/\bsotib\s+ol/u',
        '/\bola(?:man|miz)\b/u',
        '/\bijara/u',
        '/\barenda/u',
        '/\bberil(?:adi|ad)\b/u',
        '/\bkerak\b/u',
        '/\btalab\s+qilinadi\b/u',
        '/\bvakansiya/u',
        '/\bishga\s+(?:taklif|olinadi|qabul)/u',
        '/\bxizmat/u',
        '/\bta[\'’`]?mirla/u',
        '/\busta\b/u',
        '/\bnarx/u',
        '/\bkelishil/u',
        '/\bkelisha(?:miz|man)\b/u',
        '/\d\s*\$|\$\s*\d|\busd\b|\bdollar/u',
        '/\bso[\'’`]?m\b|\bsum\b|\bmln\b|\bmillion/u',
        '/\bxonali\b|\bsotix\b|\bkvartira|\bhovli\b|\buchastka/u',
        '/\bprobeg|\bholati\b|\bishlatilgan\b|\bobmen\b|\balmashtir/u',
        '/\bzakaz|\bbuyurtma|\bdostavka|\byetkazib\s+ber/u',
    ];

    // Yangilik / rasmiy xabar / tabrik belgilari
    private const NEWS_PATTERNS = [
        '/\bxabar\s+(?:berdi|beradi|berishicha|qilindi)\b/u',
        '/\bma[\'’`]?lum\s+qil(?:indi|inishicha|ishicha)\b/u',
        '/\b(?:bildirdi|ta[\'’`]?kidladi|aytib\s+o[\'’`]?tdi)\b/u',
        '/\bprezident|\bhokim(?:i|ligi|lik)?\b|\bvazirlik|\bdeputat/u',
        '/\byig[\'’`]?ilish|\bmajlis|\btadbir\b|\bmarosim/u',
        '/\btabrik|\bmuborak\b|\bbayram/u',
        '/\bob-havo|\bhavo\s+harorati|\byog[\'’`]?ingarchilik/u',
        '/\byo[\'’`]?l-transport\s+hodisa|\bjinoyat|\bprokuratura|\bichki\s+ishlar\b|\bsud\s+(?:qaror|hukm)/u',
        '/\bchempionat|\bolimpiada|\bturnir\b/u',
    ];

    private const MAX_LENGTH_WITHOUT_OFFER = 800;

    /**
     * Kanalning o'z imzosi va havolalarini olib tashlaydi.
     * Yangilik kanallari postlari oxirida "@KanalNomi" turadi — u bog'lanish manzili emas.
     */
    public function stripChannelNoise(string $text, string $channel): string
    {
        $channel = preg_quote(ltrim($channel, '@'), '/');

        $text = preg_replace('/(?:https?:\/\/)?t\.me\/\S+/iu', '', $text);
        $text = preg_replace('/@' . $channel . '\b/iu', '', $text);
        // Bot / kanal imzolari (masalan @navoiy_elon_bot) ham kontakt hisoblanmaydi
        $text = preg_replace('/@\w+bot\b/iu', '', $text);

        return trim($text);
    }

    public function hasOfferIntent(string $text): bool
    {
        return $this->matchesAny(self::OFFER_PATTERNS, $this->normalize($text));
    }

    /**
     * E'lon emasligi aniq bo'lsa sababini qaytaradi, aks holda null (post AI'ga o'tadi).
     */
    public function rejectReason(string $text): ?string
    {
        $normalized = $this->normalize($text);

        if ($this->matchesAny(self::OFFER_PATTERNS, $normalized)) {
            return null;
        }

        if ($this->matchesAny(self::NEWS_PATTERNS, $normalized)) {
            return 'yangilik/tabrik';
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH_WITHOUT_OFFER) {
            return 'uzun matn, taklif yo\'q';
        }

        return null;
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(AdAiParserService::cyrillicToLatin($text));
    }

    private function matchesAny(array $patterns, string $text): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }
}

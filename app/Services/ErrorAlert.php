<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Xatolik bo'lsa admin chatiga bot orqali ogohlantirish (+ logga yoziladi).
 * Bir xil xato spam bo'lmasin: har turi 10 daqiqada bir marta yuboriladi, oradagilar sanab boriladi.
 * Xabar javob qaytarilgandan keyin yuboriladi — foydalanuvchi kutmaydi.
 */
class ErrorAlert
{
    private const WINDOW_MINUTES = 10;

    public static function send(string $signature, string $title, array $details = []): void
    {
        Log::warning("[ErrorAlert] {$title}", $details);

        // Testlar ataylab xato chiqaradi — admin chatiga bormasin
        if (app()->runningUnitTests()) {
            return;
        }

        $key = 'error_alert:' . md5($signature);
        Cache::add("{$key}:n", 0, now()->addDay());
        Cache::increment("{$key}:n");

        // Oyna ichida allaqachon yuborilgan — faqat sanaymiz
        if (! Cache::add($key, true, now()->addMinutes(self::WINDOW_MINUTES))) {
            return;
        }
        $count = (int) Cache::pull("{$key}:n");

        $lines = ["🚨 <b>" . e($title) . "</b>"];
        if ($count > 1) {
            $lines[] = "🔁 Oxirgi marta xabar berilgandan beri {$count} marta takrorlandi";
        }
        foreach ($details as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $value = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            $lines[] = "<b>" . e($name) . ":</b> " . e(mb_strimwidth($value, 0, 600, '…'));
        }
        $lines[] = "🕒 " . now()->format('d.m.Y H:i:s');
        $html = implode("\n", $lines);

        // Xatolik javobidan keyin ham ishlashi uchun always: true (defer odatda faqat muvaffaqiyatli javobda ishlaydi)
        defer(function () use ($html) {
            try {
                app(TelegramNotifier::class)->toAdmins($html);
            } catch (\Throwable $e) {
                Log::warning("ErrorAlert yuborilmadi: {$e->getMessage()}");
            }
        }, always: true);
    }
}

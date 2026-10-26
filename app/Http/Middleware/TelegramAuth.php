<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TelegramAuth
{
    // initData shu muddatdan eski bo'lsa qabul qilinmaydi (qayta ishlatishdan himoya)
    private const MAX_AGE_SECONDS = 86400;

    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization')
            ?? $request->server('HTTP_AUTHORIZATION')
            ?? $request->server('REDIRECT_HTTP_AUTHORIZATION');

        if (!$authHeader || !str_starts_with($authHeader, 'tma ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $initDataRaw = substr($authHeader, 4);
        $botToken = (string) config('services.telegram.bot_token');

        // Telegram hujjati bo'yicha initData bir marta decode qilinadi.
        // Avvalgi usul (urldecode + parse_str) ham fallback sifatida qoldirildi, shunda hozir ishlayotgan klientlar buzilmaydi.
        $telegramData = $this->validateTelegramInitData($initDataRaw, $botToken)
            ?? $this->validateTelegramInitData(urldecode($initDataRaw), $botToken);

        if (!$telegramData) {
            return response()->json(['error' => 'Invalid Telegram hash'], 403);
        }

        $authDate = (int) ($telegramData['auth_date'] ?? 0);
        if ($authDate <= 0 || time() - $authDate > self::MAX_AGE_SECONDS) {
            return response()->json(['error' => 'Telegram init data expired'], 403);
        }

        // requestga user ma'lumotini qo'shamiz
        $request->merge([
            'telegram_user' => $telegramData['user'] ?? null,
            'telegram_data' => $telegramData,
        ]);

        return $next($request);
    }

    private function validateTelegramInitData(string $initData, string $botToken): ?array
    {
        parse_str($initData, $data);

        $receivedHash = $data['hash'] ?? null;
        if (!$receivedHash || !is_string($receivedHash)) {
            return null;
        }

        unset($data['hash']);

        ksort($data);

        $checkString = [];
        foreach ($data as $key => $value) {
            $checkString[] = $key . '=' . $value;
        }
        $checkString = implode("\n", $checkString);

        // Telegram docs bo'yicha secret key
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);

        // Hisoblashda natija hex ko'rinishida bo'lishi kerak
        $calculatedHash = hash_hmac('sha256', $checkString, $secretKey);

        // Telegram'dan kelgan hash ham hex string bo'ladi, solishtirish
        if (!hash_equals($calculatedHash, $receivedHash)) {
            return null;
        }

        if (isset($data['user']) && is_string($data['user'])) {
            $data['user'] = json_decode($data['user'], true);
        }

        return $data;
    }

}

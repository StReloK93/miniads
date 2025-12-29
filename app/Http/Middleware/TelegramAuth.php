<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TelegramAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'tma ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $initDataRaw = urldecode(substr($authHeader, 4));
        $botToken = env('TELEGRAM_BOT_TOKEN');

        $telegramData = $this->validateTelegramInitData($initDataRaw, $botToken);

        if (!$telegramData) {
            return response()->json(['error' => 'Invalid Telegram hash'], 403);
        }

        // requestga user ma'lumotini qo‘shamiz
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
        if (!$receivedHash) {
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

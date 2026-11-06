<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        // Telegram'dan kelgan barcha ma'lumotlarni olish
        $data = $request->all();
        Log::info('Telegram Webhook Keldi:', $data);

        // Botga yozgan (/start) foydalanuvchiga bot ham yoza oladi; botni to'xtatsa — endi yoza olmaydi
        if (($data['message']['chat']['type'] ?? null) === 'private') {
            User::where('telegram_user_id', $data['message']['from']['id'] ?? 0)->update(['bot_can_message' => true]);
        }
        if (isset($data['my_chat_member']['new_chat_member']['status']) && ($data['my_chat_member']['chat']['type'] ?? null) === 'private') {
            User::where('telegram_user_id', $data['my_chat_member']['from']['id'] ?? 0)
                ->update(['bot_can_message' => $data['my_chat_member']['new_chat_member']['status'] === 'member']);
        }

        // Agar kelgan so'rovda xabar (message) va matn (text) bo'lsa
        if (isset($data['message']['text'])) {
            $chatId = $data['message']['chat']['id'];

            // Javob foydalanuvchining Telegram tilida (ilova ham shu tilda ochiladi)
            $code = strtolower((string) ($data['message']['from']['language_code'] ?? ''));
            $locale = match (true) {
                str_starts_with($code, 'uz') => 'uz',
                (bool) preg_match('/^(ru|uk|be|kk|ky|tg)/', $code) => 'ru',
                $code === '' => 'uz',
                default => 'en',
            };

            // Telegram API ba'zan javob bermay qoladi (timeout) — bu bizning xatomiz emas, logga yozamiz xolos.
            // Webhook 500 qaytarsa, Telegram shu xabarni qayta-qayta yuboradi
            try {
                if ($this->isPostAdButton($data['message']['text'])) {
                    $this->sendPostAdPrompt($chatId, $locale);
                } else {
                    $this->sendWelcome($chatId, $locale);
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::warning("Webhook: Telegram'ga javob yuborilmadi (ulanish): {$e->getMessage()}");
            }
        }

        // Telegram serveriga so'rov muvaffaqiyatli qabul qilinganini bildirish
        return response()->json(['status' => 'ok'], 200);
    }

    private const LOCALES = ['uz', 'ru', 'en'];

    /** Mini app sahifasi; ?open=create — e'lon joylash (bo'lim tanlash) sahifasi ochiladi (app.ts) */
    private static function webAppUrl(?string $open = null): string
    {
        $url = config('services.telegram.web_app_url');

        return $open ? "{$url}/?open={$open}" : $url;
    }

    /** Pastki klaviaturadagi "📢 E'lon qo'yish" bosildimi (foydalanuvchi tili boshqa bo'lishi mumkin — har uchala tilda) */
    private function isPostAdButton(string $text): bool
    {
        return collect(self::LOCALES)->contains(fn (string $locale) => trim($text) === __('bot.post_ad', [], $locale));
    }

    /**
     * /start (va boshqa har qanday matn): salomlashish, kanal havolasi, ostida "Ilovani ochish" va "E'lon qo'yish".
     * Pastki doimiy "E'lon qo'yish" tugmasi alohida qisqa xabar bilan beriladi —
     * Telegram'da bitta xabarga ham ichki, ham pastki klaviatura biriktirib bo'lmaydi.
     */
    private function sendWelcome(int|string $chatId, string $locale): void
    {
        $channelUrl = config('services.telegram.channel_url');
        $channel = '@' . ltrim((string) parse_url($channelUrl, PHP_URL_PATH), '/');

        Telegraph::chat($chatId)
            ->html(__('bot.welcome', ['channel' => e($channel), 'channel_url' => e($channelUrl)], $locale))
            ->keyboard(Keyboard::make()->buttons([
                Button::make(__('bot.post_ad', [], $locale))->webApp(self::webAppUrl('create')),
                Button::make(__('bot.open_app', [], $locale))->webApp(self::webAppUrl()),
            ]))
            ->send();

        // Pastki tugma: bosilganda bot matn oladi va sendPostAdPrompt javob beradi.
        // (Pastki tugmaning o'zi mini app'ni ochmaydi: u yerdan ochilganda Telegram foydalanuvchi ma'lumotini bermaydi)
        Telegraph::chat($chatId)
            ->html(__('bot.keyboard_hint', [], $locale))
            ->replyKeyboard(ReplyKeyboard::make()->button(__('bot.post_ad', [], $locale))->resize()->persistent())
            ->send();
    }

    private function sendPostAdPrompt(int|string $chatId, string $locale): void
    {
        Telegraph::chat($chatId)
            ->html(__('bot.post_ad_prompt', [], $locale))
            ->keyboard(Keyboard::make()->buttons([
                Button::make(__('bot.post_ad', [], $locale))->webApp(self::webAppUrl('create')),
            ]))
            ->send();
    }
}
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

            Telegraph::chat($chatId)
                ->html(__('bot.welcome', [], $locale))
                ->keyboard(
                    Keyboard::make()->buttons([
                        Button::make(__('bot.open_app', [], $locale))
                            ->webApp('https://elonlar.ruzzifer.uz'),
                    ])
                )
                ->send();
        }

        // Telegram serveriga so'rov muvaffaqiyatli qabul qilinganini bildirish
        return response()->json(['status' => 'ok'], 200);
    }
}
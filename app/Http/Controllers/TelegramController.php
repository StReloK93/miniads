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
            $messageId = $data['message']['message_id'];
            $text = $data['message']['text'];

            $introText =
                "<b>Xush kelibsiz!</b>

Navoiy viloyati bo'yicha e'lonlarni qulay tarzda qidiring va joylashtiring.

• E'lonni admin bilan bog'lanmasdan o'zingiz joylashtiring
• E'lon joylashtirish — <b>bepul</b>
• Telegramdan chiqmasdan foydalaning
• Sotuvchi bilan bevosita bog'laning

<b>👇Mini App'ni ochish uchun quyidagi tugmani bosing.</b>
";

            Telegraph::chat($chatId)
                ->html($introText)
                ->keyboard(
                    Keyboard::make()->buttons([
                        // Veb-ilova (Mini App) URL manzilini ko'rsatasiz
                        Button::make("🚀 E'lonlar ilovasini ochish")
                            ->webApp('https://elonlar.ruzzifer.uz'),
                    ])
                )
                ->send();



            // Telegraph::chat($chatId)->reply($messageId)->html("Sizning <b>\"{$text}\"</b> xabaringizga javob qaytarild!")->send();
        }

        // Telegram serveriga so'rov muvaffaqiyatli qabul qilinganini bildirish
        return response()->json(['status' => 'ok'], 200);
    }
}
<?php

namespace App\Services;

use App\Jobs\DeleteTelegramMessage;
use App\Models\TelegramGroup;
use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Cache;

/**
 * Guruhda "kanalga a'zo bo'lmasa yozolmaydi" nazorati — sodda, guruhni buzmaydigan:
 *
 * - Bot guruhga ilova admini tomonidan qo'shilsa — guruh ro'yxatga olinadi; boshqa kimdir qo'shsa, bot chiqib ketadi.
 * - A'zo bo'lmagan odamning xabari o'chiriladi, u ~30 soniya yozolmaydi,
 *   bitta qisqa ogohlantirish chiqadi va u ham 30 soniyada o'chadi. Tugmalar yo'q.
 * - Guruh adminlari, kanal nomidan va anonim yozilgan xabarlar tekshirilmaydi.
 * - A'zolik natijasi keshlanadi: faol guruhda har xabar uchun Telegram'dan so'ralmaydi (API limitlari).
 */
class GroupSubscriptionGate
{
    /** A'zo (yoki guruh admini) deb topilgan odam qayta tekshirilmaydigan vaqt */
    private const ALLOWED_TTL = 900;

    /** Ogohlantirish shuncha soniya turadi */
    public const WARNING_SECONDS = 30;

    /**
     * Yozish taqiqi. Telegram 30 soniyadan qisqa taqiqni "abadiy" deb hisoblaydi —
     * shuning uchun ozgina zaxira bilan.
     */
    public const MUTE_SECONDS = 35;

    public function isGroup(array $chat): bool
    {
        return in_array($chat['type'] ?? null, ['group', 'supergroup'], true);
    }

    /** Bot guruhga qo'shildi / admin qilindi / chiqarildi */
    public function handleMembership(array $update): void
    {
        $chatId = (int) $update['chat']['id'];
        $status = $update['new_chat_member']['status'] ?? null;

        if (in_array($status, ['left', 'kicked'], true)) {
            TelegramGroup::where('chat_id', $chatId)->delete();
            Cache::forget("tg-group:{$chatId}");

            return;
        }

        $addedBy = (int) ($update['from']['id'] ?? 0);
        $isAppAdmin = User::where('telegram_user_id', $addedBy)->where('role', 'admin')->exists();

        if (! $isAppAdmin) {
            // Ro'yxatdagi guruhda boshqa admin botning huquqini o'zgartirsa — guruh qolaveradi
            if (! TelegramGroup::where('chat_id', $chatId)->exists()) {
                Telegraph::chat($chatId)->leaveChat()->send();
            }

            return;
        }

        TelegramGroup::updateOrCreate(['chat_id' => $chatId], [
            'title' => $update['chat']['title'] ?? null,
            'added_by' => $addedBy,
        ]);
        Cache::forget("tg-group:{$chatId}");
    }

    /** Guruhdagi oddiy xabar: a'zo bo'lmasa — o'chiriladi, qisqa muddat yozish taqiqlanadi va ogohlantiriladi */
    public function handleMessage(array $message): void
    {
        $chatId = (int) $message['chat']['id'];
        $from = $message['from'] ?? null;

        // Kanal yoki anonim admin nomidan yozilgan, bot va xizmat xabarlari (kimdir qo'shildi/chiqdi) tekshirilmaydi
        if (! $from || ($from['is_bot'] ?? false) || isset($message['sender_chat'])
            || isset($message['new_chat_members']) || isset($message['left_chat_member'])) {
            return;
        }
        if (! $this->isTracked($chatId) || $this->isAllowed($chatId, (int) $from['id'])) {
            return;
        }

        Telegraph::chat($chatId)->deleteMessage((int) $message['message_id'])->send();
        $this->mute($chatId, (int) $from['id']);
        $this->warn($chatId, $from);
    }

    private function isTracked(int $chatId): bool
    {
        return Cache::remember("tg-group:{$chatId}", 300, fn () => TelegramGroup::where('chat_id', $chatId)->exists());
    }

    private function isAllowed(int $chatId, int $userId): bool
    {
        $key = "tg-gate:ok:{$chatId}:{$userId}";
        if (Cache::get($key)) {
            return true;
        }

        $allowed = $this->isGroupAdmin($chatId, $userId) || $this->isChannelMember($userId);
        if ($allowed) {
            Cache::put($key, true, self::ALLOWED_TTL);
        }

        return $allowed;
    }

    private function isGroupAdmin(int $chatId, int $userId): bool
    {
        $status = Telegraph::chat($chatId)->chatMember((string) $userId)->send()->json('result.status');

        return in_array($status, ['creator', 'administrator'], true);
    }

    /** Bot kanalda admin bo'lishi shart — aks holda Telegram a'zolar ro'yxatini bermaydi */
    private function isChannelMember(int $userId): bool
    {
        $result = Telegraph::chat($this->channel())->chatMember((string) $userId)->send()->json('result');
        $status = $result['status'] ?? null;

        return in_array($status, ['creator', 'administrator', 'member'], true)
            || ($status === 'restricted' && ($result['is_member'] ?? false));
    }

    /** Qisqa muddat yozolmaydi (bot guruhda "A'zolarni cheklash" huquqiga ega bo'lishi kerak) */
    private function mute(int $chatId, int $userId): void
    {
        Telegraph::chat($chatId)
            ->restrictChatMember((string) $userId, [
                'can_send_messages' => false,
                'can_send_audios' => false,
                'can_send_documents' => false,
                'can_send_photos' => false,
                'can_send_videos' => false,
                'can_send_video_notes' => false,
                'can_send_voice_notes' => false,
                'can_send_polls' => false,
                'can_send_other_messages' => false,
                'can_add_web_page_previews' => false,
            ])
            ->withData('chat_id', $chatId)
            ->until(now()->addSeconds(self::MUTE_SECONDS))
            ->send();
    }

    private function warn(int $chatId, array $from): void
    {
        $userId = (int) $from['id'];
        // Bir vaqtda kelgan bir nechta xabar uchun — bitta ogohlantirish
        if (! Cache::add("tg-gate:warned:{$chatId}:{$userId}", true, self::WARNING_SECONDS)) {
            return;
        }

        $locale = TelegramNotifier::localeFor($from['language_code'] ?? null);
        $name = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: ($from['username'] ?? '');

        $response = Telegraph::chat($chatId)
            ->html(__('bot.gate_warning', [
                'mention' => '<a href="tg://user?id=' . $userId . '">' . e($name) . '</a>',
                'channel' => e($this->channel()),
            ], $locale))
            ->withoutPreview()
            ->send();

        $messageId = $response->telegraphMessageId();
        if ($messageId) {
            DeleteTelegramMessage::dispatch($chatId, $messageId)->delay(now()->addSeconds(self::WARNING_SECONDS));
        }
    }

    /** A'zolik talab qilinadigan kanal: "@navoiy_elonlaruz" */
    private function channel(): string
    {
        return '@' . ltrim((string) parse_url((string) config('services.telegram.channel_url'), PHP_URL_PATH), '/');
    }
}

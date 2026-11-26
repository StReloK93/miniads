<?php

namespace App\Services;

use App\Models\ChannelMember;
use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Foydalanuvchi e'lon kanallariga (config services.telegram.channels) a'zomi.
 *
 * Telegram kanal a'zolari ro'yxatini bermaydi, shuning uchun har foydalanuvchi alohida
 * getChatMember bilan so'raladi (bot kanalda admin bo'lishi shart — u yerga post yozadi, demak admin).
 */
class ChannelMembershipService
{
    private const MEMBER_STATUSES = ['creator', 'administrator', 'member'];

    /** @return list<string> */
    public function channelIds(): array
    {
        return array_values(array_map('strval', config('services.telegram.channels', [])));
    }

    /**
     * Kanallar nomi bilan (admin panel uchun). Nom Telegram'dan bir kunga keshlanadi.
     *
     * @return list<array{id: string, title: string, username: ?string}>
     */
    public function channels(): array
    {
        return array_map(fn (string $id) => Cache::remember("channel-info:{$id}", 86400, function () use ($id) {
            try {
                $info = Telegraph::chat($id)->chatInfo()->send()->json('result') ?? [];
            } catch (\Throwable $e) {
                Log::warning("Kanal ma'lumoti olinmadi ({$id}): {$e->getMessage()}");
                $info = [];
            }

            return [
                'id' => $id,
                'title' => $info['title'] ?? $id,
                'username' => $info['username'] ?? null,
            ];
        }), $this->channelIds());
    }

    /**
     * Bitta foydalanuvchini barcha kanallarda tekshiradi va natijani yozadi.
     * Telegram javob bermasa (ulanish, limit) — eski natija o'zgarmaydi, false qaytadi.
     */
    public function check(User $user): bool
    {
        if (! $user->telegram_user_id) {
            return false;
        }

        $allOk = true;
        foreach ($this->channelIds() as $chatId) {
            $status = $this->fetchStatus($chatId, (int) $user->telegram_user_id);
            if ($status === null) {
                $allOk = false;
                continue;
            }
            $this->store($user->id, $chatId, $status['status'], $status['is_member']);
        }

        // Muvaffaqiyatsiz bo'lsa ham vaqt yoziladi — navbat bitta "muammoli" odamda tiqilib qolmasin
        $user->forceFill(['channels_checked_at' => now()])->saveQuietly();

        return $allOk;
    }

    /** Kanaldan kelgan "chat_member" yangilanishi (kimdir qo'shildi / chiqdi) */
    public function handleUpdate(array $update): bool
    {
        $chat = $update['chat'] ?? [];
        $chatId = $this->matchChannel($chat);
        $telegramUserId = $update['new_chat_member']['user']['id'] ?? null;
        if ($chatId === null || ! $telegramUserId) {
            return false;
        }

        $user = User::where('telegram_user_id', $telegramUserId)->first();
        if ($user) {
            $member = $update['new_chat_member'];
            $this->store($user->id, $chatId, (string) ($member['status'] ?? 'left'), $this->isMember($member));
        }

        return true;
    }

    /** @return array{status: string, is_member: bool}|null */
    private function fetchStatus(string $chatId, int $telegramUserId): ?array
    {
        try {
            $response = Telegraph::chat($chatId)->chatMember((string) $telegramUserId)->send();
        } catch (\Throwable $e) {
            Log::warning("Kanal a'zoligi tekshirilmadi ({$chatId}): {$e->getMessage()}");

            return null;
        }

        $result = $response->json('result');
        if (is_array($result)) {
            return ['status' => (string) ($result['status'] ?? 'left'), 'is_member' => $this->isMember($result)];
        }

        // Telegram bu odamni umuman tanimaydi — demak kanalda ham yo'q
        $description = (string) $response->json('description');
        if (str_contains($description, 'user not found') || str_contains($description, 'PARTICIPANT_ID_INVALID')) {
            return ['status' => 'left', 'is_member' => false];
        }

        Log::warning("Kanal a'zoligi tekshirilmadi ({$chatId}): {$description}");

        return null;
    }

    private function isMember(array $member): bool
    {
        $status = $member['status'] ?? null;

        return in_array($status, self::MEMBER_STATUSES, true)
            || ($status === 'restricted' && ($member['is_member'] ?? false));
    }

    private function store(int $userId, string $chatId, string $status, bool $isMember): void
    {
        ChannelMember::updateOrCreate(
            ['user_id' => $userId, 'chat_id' => $chatId],
            ['status' => $status, 'is_member' => $isMember, 'checked_at' => now()],
        );
    }

    /** Yangilanishdagi chat — bizning kanallardan biri bo'lsa, config'dagi ko'rinishi */
    private function matchChannel(array $chat): ?string
    {
        $id = isset($chat['id']) ? (string) $chat['id'] : null;
        $username = isset($chat['username']) ? '@' . strtolower($chat['username']) : null;

        foreach ($this->channelIds() as $configured) {
            if ($configured === $id || strtolower($configured) === $username) {
                return $configured;
            }
        }

        return null;
    }
}

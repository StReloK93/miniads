<?php

namespace App\Services;

use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Facades\Log;

/**
 * Bot orqali shaxsiy xabarlar (foydalanuvchiga) va admin chatiga ogohlantirishlar.
 * Xabar yuborilmasa jim o'tadi: bildirishnoma asosiy amalni (e'lon joylash, shikoyat) hech qachon buzmasligi kerak.
 */
class TelegramNotifier
{
    /** Mini app'ni kerakli joyida ochadigan havola (app.ts: startapp=product_12, my_ads, ss_5, admin_reported) */
    public static function appLink(string $startParam): string
    {
        return rtrim((string) config('services.telegram.mini_app_url'), '/') . '?startapp=' . rawurlencode($startParam);
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $buttons  [matn, havola] — har biri alohida qatorda
     */
    public function toUser(User $user, string $html, array $buttons = [], ?string $photoUrl = null): bool
    {
        if (! $user->bot_can_message || ! $user->telegram_user_id || $user->isBlocked()) {
            return false;
        }

        $ok = $this->send((string) $user->telegram_user_id, $html, $buttons, $photoUrl, $description);

        // Foydalanuvchi botni to'xtatgan/o'chirgan: keyingi safar urinib o'tirmaymiz (ilovada qayta ruxsat so'raladi)
        $isUnreachable = $description && preg_match('/blocked|deactivated|chat not found|forbidden/i', $description);
        if (! $ok && $isUnreachable) {
            $user->forceFill(['bot_can_message' => false])->save();

            return false;
        }

        // Rasmni Telegram yuklay olmadi — xabarning o'zi yo'qolmasin, rasmsiz yuboramiz
        if (! $ok && $photoUrl) {
            $ok = $this->send((string) $user->telegram_user_id, $html, $buttons);
        }

        return $ok;
    }

    public function toAdmins(string $html, array $buttons = []): bool
    {
        $chatId = config('services.telegram.admin_chat_id');

        return $chatId ? $this->send((string) $chatId, $html, $buttons) : false;
    }

    /**
     * Admin rolidagi foydalanuvchilarning o'ziga shaxsan (texnik ogohlantirishlar kanal/guruhga ketmasin).
     * bot_can_message tekshirilmaydi: admin xabarni o'tkazib yubormasin — botni ishga tushirmagan bo'lsa Telegram o'zi rad etadi.
     */
    public function toAdminUsers(string $html): bool
    {
        $sent = false;
        User::where('role', 'admin')->whereNotNull('telegram_user_id')->pluck('telegram_user_id')
            ->each(function ($chatId) use ($html, &$sent) {
                $sent = $this->send((string) $chatId, $html, []) || $sent;
            });

        return $sent;
    }

    private function send(string $chatId, string $html, array $buttons, ?string $photoUrl = null, ?string &$description = null): bool
    {
        try {
            $message = Telegraph::chat($chatId);
            if ($photoUrl) {
                $message = $message->photo($photoUrl);
            }
            $message = $message->html($html);

            if ($buttons) {
                $message = $message->keyboard(Keyboard::make()->buttons(
                    array_map(fn (array $button) => Button::make($button[0])->url($button[1]), $buttons),
                ));
            }

            $response = $message->send();
            if ($response->telegraphOk()) {
                return true;
            }

            $description = (string) $response->json('description');
            Log::info("Bot xabari yuborilmadi ({$chatId}): {$description}");
        } catch (\Throwable $e) {
            $description = $e->getMessage();
            Log::warning("Bot xabari xatosi ({$chatId}): {$description}");
        }

        return false;
    }
}

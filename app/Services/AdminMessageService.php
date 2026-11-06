<?php

namespace App\Services;

use App\Models\AdminMessage;
use App\Models\Product;
use App\Models\User;

/**
 * Admin → e'lon muallifi: ogohlantirish yoki tushuntirish.
 * Xabar bazada saqlanadi (ilovada Profil bo'limida ko'rsatiladi) va imkon bo'lsa bot orqali ham yuboriladi.
 */
class AdminMessageService
{
    public function __construct(private readonly TelegramNotifier $notifier)
    {
    }

    public function send(User $recipient, User $sender, string $type, string $body, ?Product $product = null): AdminMessage
    {
        $message = AdminMessage::create([
            'user_id' => $recipient->id,
            'product_id' => $product?->id,
            'sender_id' => $sender->id,
            'type' => $type,
            'body' => trim($body),
        ]);

        $sent = $this->sendToTelegram($recipient, $message, $product);
        $message->forceFill(['telegram_sent' => $sent])->save();

        return $message;
    }

    private function sendToTelegram(User $recipient, AdminMessage $message, ?Product $product): bool
    {
        if (! $recipient->telegram_user_id) {
            return false;
        }

        // Foydalanuvchi tilida; keyin admin so'rovining tiliga qaytamiz
        $previousLocale = app()->getLocale();
        app()->setLocale($recipient->language ?? 'uz');

        try {
            $lines = [__($message->type === 'warning' ? 'bot.admin_warning' : 'bot.admin_info')];
            if ($product) {
                $lines[] = __('bot.admin_about_ad', ['title' => e($product->title)]);
            }
            $lines[] = '';
            $lines[] = e($message->body);

            $button = $product
                ? [__('bot.open_ad'), TelegramNotifier::appLink("product_{$product->id}")]
                : [__('bot.open_app'), TelegramNotifier::appLink('my_ads')];

            // Bloklangan yoki botga ruxsat bermagan bo'lsa ham urinamiz: admin xabari muhim (Telegram o'zi rad etishi mumkin)
            return $this->notifier->toUser($recipient, implode("\n", $lines), [$button], force: true);
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}

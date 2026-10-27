<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Muddati 2 kun ichida tugaydigan e'lonlar egalariga bitta xabar (har bir muddat uchun bir marta).
 * E'lon uzaytirilsa expires_at o'zgaradi va keyingi muddat tugashida yana eslatiladi.
 */
class NotifyExpiringProducts extends Command
{
    protected $signature = 'notifications:expiring';

    protected $description = "Muddati tugayotgan e'lonlar haqida egalariga bot orqali eslatma yuboradi";

    public function handle(TelegramNotifier $notifier): int
    {
        $products = Product::query()
            ->setEagerLoads([])
            ->select(['id', 'user_id', 'title', 'expires_at', 'expiry_reminded_for'])
            ->where('is_bot', false)
            ->whereBetween('expires_at', [now(), now()->addDays(2)])
            ->where(fn ($query) => $query
                ->whereNull('expiry_reminded_for')
                ->orWhereColumn('expiry_reminded_for', '!=', 'expires_at'))
            ->whereHas('user', fn ($query) => $query
                ->where('notify_expiry', true)
                ->where('bot_can_message', true)
                ->whereNull('blocked_at'))
            ->get()
            ->groupBy('user_id');

        $sent = 0;
        foreach ($products as $userId => $items) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $lines = $items
                ->map(fn (Product $product) => '• ' . e($product->title) . ' — ' . Carbon::parse($product->expires_at)->format('d.m H:i'))
                ->implode("\n");
            $heading = $items->count() > 1
                ? "⏳ <b>{$items->count()} ta e'loningiz muddati tugayapti:</b>"
                : "⏳ <b>E'loningiz muddati tugayapti:</b>";

            $ok = $notifier->toUser(
                $user,
                "{$heading}\n\n{$lines}\n\nUzaytirsangiz, e'lon yana lenta boshiga chiqadi.",
                [['Uzaytirish', TelegramNotifier::appLink('my_ads')]],
            );

            if ($ok) {
                $items->each(fn (Product $product) => $product->forceFill(['expiry_reminded_for' => $product->expires_at])->saveQuietly());
                $sent++;
            }
        }

        $this->info("Yuborilgan eslatmalar: {$sent}");

        return self::SUCCESS;
    }
}

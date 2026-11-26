<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ChannelMembershipService;
use Illuminate\Console\Command;

/**
 * Foydalanuvchilarning kanal a'zoligini bosqichma-bosqich tekshiradi: hali tekshirilmaganlar,
 * keyin eng eski tekshirilganlar. Har ishga tushishda --limit nafar (Telegram limiti ~30 so'rov/soniya).
 */
class CheckChannelMembers extends Command
{
    protected $signature = 'users:check-channels {--limit=300 : Bir ishga tushishda nechta foydalanuvchi} {--all : Hammasini qayta tekshirish (yangi tekshirilganlarni ham)}';

    protected $description = "Foydalanuvchilar e'lon kanaliga a'zomi — Telegram'dan tekshirib yozadi";

    public function handle(ChannelMembershipService $service): int
    {
        if ($service->channelIds() === []) {
            $this->warn('Kanal sozlanmagan (TELEGRAM_CHANNELS).');

            return self::SUCCESS;
        }

        $botUserId = User::where('username', 'telegramAssistent')->value('id');
        $users = User::query()
            ->whereNotNull('telegram_user_id')
            ->when($botUserId, fn ($query) => $query->where('id', '!=', $botUserId))
            ->when(! $this->option('all'), fn ($query) => $query->where(fn ($query) => $query
                ->whereNull('channels_checked_at')
                ->orWhere('channels_checked_at', '<', now()->subDay())))
            ->orderByRaw('channels_checked_at IS NOT NULL')
            ->orderBy('channels_checked_at')
            ->limit((int) $this->option('limit'))
            ->get(['id', 'telegram_user_id', 'channels_checked_at']);

        $failed = 0;
        foreach ($users as $user) {
            if (! $service->check($user)) {
                $failed++;
            }
            // Har kanalga bitta so'rov: ~20 so'rov/soniyadan oshmaydi
            usleep(50_000 * count($service->channelIds()));
        }

        $this->info("Tekshirildi: {$users->count()}, xato: {$failed}");

        return self::SUCCESS;
    }
}

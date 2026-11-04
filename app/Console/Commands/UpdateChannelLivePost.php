<?php

namespace App\Console\Commands;

use App\Services\ChannelLivePostService;
use Illuminate\Console\Command;

/**
 * Kanaldagi qadalgan "jonli" postni yangilaydi (admin paneldan yoqilgan bo'lsa).
 * Raqamlar o'zgarmagan bo'lsa Telegram'ga so'rov yuborilmaydi.
 */
class UpdateChannelLivePost extends Command
{
    protected $signature = 'channel:live-post {--force : Raqamlar o\'zgarmagan bo\'lsa ham yangilash}';

    protected $description = "Kanaldagi qadalgan \"Hozir faol e'lonlar\" postini yangilaydi";

    public function handle(ChannelLivePostService $service): int
    {
        if (! ChannelLivePostService::enabled()) {
            $this->line("Jonli post o'chirilgan (admin panel → Kanalga post).");
            return self::SUCCESS;
        }

        foreach ($service->sync((bool) $this->option('force')) as $channel => $result) {
            $this->line("{$channel}: {$result}");
        }

        return self::SUCCESS;
    }
}

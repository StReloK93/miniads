<?php

namespace Database\Seeders;

use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Seeder;

class TelegraphSeeder extends Seeder
{
    public function run(): void
    {
        $token = config('telegraph.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        if (! $token) {
            return;
        }

        $name = env('TELEGRAM_BOT_NAME', 'NavoiyElonBot');

        $bot = TelegraphBot::firstOrCreate(
            ['token' => $token],
            ['name' => $name]
        );

        $channelId = env('TELEGRAM_CHANNEL', '-1003997124291');
        if ($channelId) {
            TelegraphChat::firstOrCreate(
                [
                    'chat_id' => $channelId,
                    'telegraph_bot_id' => $bot->id,
                ],
                [
                    'name' => 'Main Channel',
                ]
            );
        }
    }
}

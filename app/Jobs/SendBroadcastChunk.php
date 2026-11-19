<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Models\User;
use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Postni bir guruh (~25 ta) bot obunachisiga yuboradi. Guruhlar bir necha soniya oralig'ida navbatga qo'yiladi —
 * Telegram cheklovidan (soniyasiga ~30 xabar) oshmaslik uchun.
 * Botni to'xtatgan foydalanuvchi TelegramNotifier'da "bot yoza olmaydi" deb belgilanadi.
 */
class SendBroadcastChunk implements ShouldQueue
{
    use Queueable;

    /** Qayta urinilmaydi: yarmi yuborilgan guruh ikkinchi marta yuborilsa odamlarga ikki marta boradi */
    public int $tries = 1;

    public int $timeout = 50;

    /** @param  array<int, int>  $userIds */
    public function __construct(public int $broadcastId, public array $userIds)
    {
    }

    public function handle(TelegramNotifier $notifier): void
    {
        $broadcast = Broadcast::find($this->broadcastId);
        if (! $broadcast) {
            return;
        }

        $buttons = $broadcast->button_text && $broadcast->button_url ? [[$broadcast->button_text, $broadcast->button_url]] : [];
        $sent = 0;
        $failed = 0;

        foreach (User::whereIn('id', $this->userIds)->get() as $user) {
            // Rasm: file_id bor bo'lsa — u (tez, qayta yuklanmaydi), yo'q bo'lsa — diskdagi fayl
            $photo = $broadcast->photo_file_id ?: $broadcast->photo_path;
            $fileId = null;

            $ok = $notifier->toUser($user, $broadcast->html, $buttons, $photo, false, $fileId);
            $ok ? $sent++ : $failed++;

            if ($fileId && ! $broadcast->photo_file_id) {
                $broadcast->forceFill(['photo_file_id' => $fileId])->saveQuietly();
            }
            usleep(60_000);
        }

        // Ro'yxatdan o'chirilgan foydalanuvchilar ham "yetmadi" qatoriga
        $failed += count($this->userIds) - $sent - $failed;

        DB::table('broadcasts')->where('id', $broadcast->id)->update([
            'sent' => DB::raw('sent + ' . (int) $sent),
            'failed' => DB::raw('failed + ' . (int) $failed),
            'updated_at' => now(),
        ]);
        $broadcast->finishIfDone();
    }
}

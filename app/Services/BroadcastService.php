<?php

namespace App\Services;

use App\Jobs\SendBroadcastChunk;
use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Bot obunachilariga post: kimga boradi, navbatga qo'yish va o'ziga sinov uchun yuborish.
 */
class BroadcastService
{
    private const CHUNK = 25;

    /** Guruhlar orasidagi soniya: 25 xabar / 2 s ≈ soniyasiga 12 ta (Telegram cheklovi ~30) */
    private const CHUNK_DELAY_SECONDS = 2;

    public function __construct(private TelegramNotifier $notifier)
    {
    }

    /** Bot xabar yubora oladigan, bloklanmagan foydalanuvchilar */
    public static function audience(): Builder
    {
        return User::query()
            ->whereNotNull('telegram_user_id')
            ->where('bot_can_message', true)
            ->whereNull('blocked_at');
    }

    /**
     * @param  string|null  $photoPath  tayyorlangan JPEG (ChannelPostService::preparePhoto); yuborish tugagach o'chiriladi
     */
    public function start(User $author, string $text, string $html, ?string $photoPath, ?string $buttonText, ?string $buttonUrl): Broadcast
    {
        $ids = self::audience()->orderBy('id')->pluck('id');

        $broadcast = Broadcast::create([
            'created_by' => $author->id,
            'text' => $text,
            'html' => $html,
            'photo_path' => $photoPath,
            'button_text' => $buttonText,
            'button_url' => $buttonUrl,
            'total' => $ids->count(),
        ]);

        $ids->chunk(self::CHUNK)->values()->each(function ($chunk, int $index) use ($broadcast) {
            SendBroadcastChunk::dispatch($broadcast->id, $chunk->values()->all())
                ->delay(now()->addSeconds($index * self::CHUNK_DELAY_SECONDS));
        });

        // Obunachi yo'q — darhol tugagan
        $broadcast->finishIfDone();

        return $broadcast;
    }

    /** Sinov: faqat adminning o'ziga (bot unga yoza olmasa ham urinib ko'riladi) */
    public function sendTest(User $admin, string $html, ?string $photoPath, ?string $buttonText, ?string $buttonUrl): bool
    {
        $buttons = $buttonText && $buttonUrl ? [[$buttonText, $buttonUrl]] : [];

        return $this->notifier->toUser($admin, $html, $buttons, $photoPath, true);
    }
}

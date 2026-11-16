<?php

namespace App\Jobs;

use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Guruhdagi vaqtinchalik bot xabari (ogohlantirish) biroz turib o'chiriladi — guruh axlatga to'lmasin */
class DeleteTelegramMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $chatId, public int $messageId) {}

    public function handle(): void
    {
        // Odam tugmani bosib, xabar allaqachon o'chgan bo'lishi mumkin — Telegram xatosi bizni qiziqtirmaydi
        try {
            Telegraph::chat($this->chatId)->deleteMessage($this->messageId)->send();
        } catch (\Throwable) {
        }
    }
}

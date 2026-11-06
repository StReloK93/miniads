<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\TelegramProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * E'lonni kanal(lar)ga chiqarish, yangilash yoki kanaldan o'chirish — fonda (queue).
 * Foydalanuvchi Telegram javobini kutmaydi; Telegram sekinlashsa yoki xato bersa job qayta urinadi.
 * Ishchi: routes/console.php dagi queue:work (scheduler har daqiqa ishga tushiradi).
 */
class SyncProductToTelegram implements ShouldQueue
{
    use Queueable;

    public const PUBLISH = 'publish';
    public const UPDATE = 'update';
    public const DELETE = 'delete';

    public int $tries = 3;

    /** @var int[] Qayta urinishlar orasidagi kutish (soniya) */
    public array $backoff = [30, 120];

    public function __construct(
        public int $productId,
        public string $action,
        public bool $previouslyHadImage = false,
    ) {
        // E'lon bazaga to'liq yozilgandan (commit) keyin ishga tushsin
        $this->afterCommit();
    }

    public function handle(TelegramProductService $telegram): void
    {
        $product = Product::withTrashed()->find($this->productId);
        if (! $product) {
            return;
        }

        match ($this->action) {
            // Navbatda turganda o'chirilgan e'lon kanalga chiqmasin
            self::PUBLISH => $product->trashed() ? null : $telegram->publish($product),
            self::UPDATE => $product->trashed() ? null : $telegram->synchronize($product, $this->previouslyHadImage),
            self::DELETE => $telegram->deleteFromChannels($product),
        };
    }
}

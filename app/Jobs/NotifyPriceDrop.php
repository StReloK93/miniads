<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\User;
use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * E'lon arzonlasa — uni sevimlilarga qo'shganlarga bot orqali xabar (foydalanuvchi tilida).
 * Bir e'lon bo'yicha bir odamga sutkada bir martadan ko'p yozilmaydi (narx qayta-qayta tahrirlansa ham).
 */
class NotifyPriceDrop implements ShouldQueue
{
    use Queueable;

    private const COOLDOWN_HOURS = 24;

    public int $tries = 2;

    public function __construct(public int $productId, public int $oldPrice)
    {
        $this->afterCommit();
    }

    public function handle(TelegramNotifier $notifier): void
    {
        $product = Product::active()->with(['price_type', 'images'])->find($this->productId);
        if (! $product || $product->price === null || $product->price >= $this->oldPrice) {
            return;
        }

        $users = User::query()
            ->whereIn('id', $product->favorites()->select('user_id'))
            ->where('id', '!=', $product->user_id)
            ->where('notify_price_drop', true)
            ->where('bot_can_message', true)
            ->whereNull('blocked_at')
            ->get();

        $image = $product->images->first();
        $photoUrl = $image ? url('storage/' . ltrim($image->thumb_src ?: $image->src, '/')) : null;
        $previousLocale = app()->getLocale();

        foreach ($users as $user) {
            if (! Cache::add("price_drop:{$product->id}:{$user->id}", true, now()->addHours(self::COOLDOWN_HOURS))) {
                continue;
            }

            app()->setLocale($user->language ?? 'uz');
            $notifier->toUser(
                $user,
                __('bot.price_drop', [
                    'title' => e($product->title),
                    'old' => $this->formatPrice($this->oldPrice, $product),
                    'new' => $this->formatPrice((int) $product->price, $product),
                ]),
                [[__('bot.open_ad'), TelegramNotifier::appLink("product_{$product->id}")]],
                $photoUrl,
            );
        }

        app()->setLocale($previousLocale);
    }

    private function formatPrice(int $value, Product $product): string
    {
        $number = number_format($value, 0, '.', ' ');
        $unit = $product->price_type?->type;

        if (! $unit) {
            return $number;
        }

        return $product->price_type->position === 'left' ? e($unit) . ' ' . $number : $number . ' ' . e($unit);
    }
}

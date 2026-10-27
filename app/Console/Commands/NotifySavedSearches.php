<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\SavedSearch;
use App\Services\ProductSearchService;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

/**
 * Kuzatuvlar: oxirgi tekshiruvdan keyin chiqqan mos e'lonlar haqida bitta xabar (3 tasi tugma bilan).
 * Har 15 daqiqada ishlaydi — bitta kuzatuv bo'yicha xabar ham ko'pi bilan shu tezlikda keladi.
 */
class NotifySavedSearches extends Command
{
    protected $signature = 'notifications:saved-searches';

    protected $description = "Kuzatilayotgan qidiruvlarga mos yangi e'lonlar haqida bot orqali xabar yuboradi";

    private const BUTTONS_LIMIT = 3;

    public function handle(ProductSearchService $search, TelegramNotifier $notifier): int
    {
        // Tekshiruv boshidagi chegara: shu orada qo'shilgan e'lon keyingi safar ko'riladi, tushib qolmaydi
        $maxId = (int) Product::withTrashed()->max('id');
        $sent = 0;

        SavedSearch::query()
            ->with('user')
            ->where('is_paused', false)
            ->where('last_product_id', '<', $maxId)
            ->whereHas('user', fn ($query) => $query
                ->where('notify_saved_searches', true)
                ->where('bot_can_message', true)
                ->whereNull('blocked_at'))
            ->chunkById(100, function ($searches) use ($search, $notifier, $maxId, &$sent): void {
                foreach ($searches as $saved) {
                    $request = Request::create('/', 'GET', [...($saved->params ?? []), 'search' => $saved->query]);
                    $ids = $search->matchingIds($request, fn ($query) => $query
                        ->whereBetween('id', [$saved->last_product_id + 1, $maxId])
                        ->where('user_id', '!=', $saved->user_id));

                    $saved->last_product_id = $maxId;

                    if ($ids && $this->notify($notifier, $saved, $ids)) {
                        $saved->last_notified_at = now();
                        $sent++;
                    }
                    $saved->save();
                }
            });

        $this->info("Yuborilgan xabarlar: {$sent}");

        return self::SUCCESS;
    }

    /** @param int[] $ids */
    private function notify(TelegramNotifier $notifier, SavedSearch $saved, array $ids): bool
    {
        $products = Product::query()
            ->with(['price_type', 'images'])
            ->whereIn('id', array_slice($ids, 0, self::BUTTONS_LIMIT))
            ->get();

        // Xabar foydalanuvchi tilida (narx birligi "сум" ham shu tilda)
        $locale = $saved->user->language ?? 'uz';
        app()->setLocale($locale);

        $count = count($ids);
        $lines = $products->map(fn (Product $product) => '• ' . e($product->title) . $this->price($product))->implode("\n");
        $rest = $count - self::BUTTONS_LIMIT;
        $more = $rest > 0 ? "\n" . trans_choice('bot.more', $rest, ['count' => $rest]) : '';

        $buttons = $products
            ->map(fn (Product $product) => [mb_strimwidth($product->title, 0, 40, '…'), TelegramNotifier::appLink("product_{$product->id}")])
            ->all();
        if ($rest > 0) {
            $buttons[] = [__('bot.show_all', ['count' => $count]), TelegramNotifier::appLink("ss_{$saved->id}")];
        }

        $photo = $products->first()?->images->first();
        $heading = trans_choice('bot.new_ads', $count, ['count' => $count, 'label' => e($saved->label)]);

        return $notifier->toUser(
            $saved->user,
            "{$heading}\n\n{$lines}{$more}",
            $buttons,
            $photo ? asset('storage/' . ltrim($photo->src, '/')) : null,
        );
    }

    private function price(Product $product): string
    {
        if (! $product->price) {
            return '';
        }

        $amount = number_format((float) $product->price, 0, '.', ' ');
        $unit = $product->price_type?->translated('type') ?? '';

        return " — <b>{$amount} {$unit}</b>";
    }
}

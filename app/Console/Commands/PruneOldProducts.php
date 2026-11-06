<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eski e'lonlarni rasmlari bilan butunlay o'chiradi — disk va baza cheksiz o'smasin.
 *  - arxivlangan (o'chirilgan) e'lon: ARCHIVED_DAYS kundan keyin;
 *  - Telegram assistent e'loni: muddati tugaganiga BOT_EXPIRED_DAYS kun bo'lsa (uni hech kim qayta yoqmaydi);
 *  - foydalanuvchi e'loni: muddati tugaganiga USER_EXPIRED_DAYS kun bo'lsa (shu vaqtgacha "Faollashtirish" mumkin).
 * Muddatsiz (expires_at = null) e'lonlarga tegilmaydi.
 */
class PruneOldProducts extends Command
{
    public const ARCHIVED_DAYS = 90;
    public const BOT_EXPIRED_DAYS = 30;
    public const USER_EXPIRED_DAYS = 180;

    protected $signature = 'products:prune {--dry-run : Faqat nechta o\'chishini ko\'rsatadi}';

    protected $description = "Eski arxiv va muddati o'tgan e'lonlarni rasmlari bilan o'chirish";

    public function handle(ProductService $productService): int
    {
        $groups = [
            'arxivlangan' => Product::onlyTrashed()->where('deleted_at', '<', now()->subDays(self::ARCHIVED_DAYS)),
            'bot, muddati o\'tgan' => Product::where('is_bot', true)
                ->whereNotNull('expires_at')->where('expires_at', '<', now()->subDays(self::BOT_EXPIRED_DAYS)),
            'foydalanuvchi, muddati o\'tgan' => Product::where('is_bot', false)
                ->whereNotNull('expires_at')->where('expires_at', '<', now()->subDays(self::USER_EXPIRED_DAYS)),
        ];

        $total = 0;
        foreach ($groups as $label => $query) {
            /** @var Builder $query */
            $count = (clone $query)->count();
            $this->line("{$label}: {$count}");

            if (! $this->option('dry-run')) {
                $query->setEagerLoads([])->chunkById(100, function ($products) use ($productService): void {
                    $products->each(fn (Product $product) => $productService->forceDelete($product));
                });
            }
            $total += $count;
        }

        $this->info(($this->option('dry-run') ? "O'chiriladi" : "O'chirildi") . ": {$total} ta e'lon");

        return self::SUCCESS;
    }
}

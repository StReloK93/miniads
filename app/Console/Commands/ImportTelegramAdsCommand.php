<?php

namespace App\Console\Commands;

use App\Models\BotSetting;
use App\Services\Bot\BotAdImportService;
use Illuminate\Console\Command;

class ImportTelegramAdsCommand extends Command
{
    protected $signature = 'ads:import-telegram {--force : Sozlama o\'chiq bo\'lsa ham majburiy ishga tushirish}';
    protected $description = 'Telegram kanallardan yangi e\'lonlarni avtomatik skrap qilib bazaga joylash';

    public function handle(BotAdImportService $service): int
    {
        $this->info("Avto-e'lonlar importi boshlandi...");

        if ($this->option('force')) {
            $setting = BotSetting::current();
            $setting->update(['is_enabled' => true]);
        }

        $result = $service->importBatch();

        $this->info("Natija: " . ($result['message'] ?? $result['status']));
        $this->info("Ushbu siklda qo'shildi: " . ($result['imported_count'] ?? 0) . " ta e'lon.");

        if (!empty($result['telegram_published_product_id'])) {
            $this->info("Telegram kanalga yuborildi: Product #{$result['telegram_published_product_id']}");
        }

        return self::SUCCESS;
    }
}

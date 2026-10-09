<?php

namespace App\Services\Bot;

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\TelegramProductService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class BotAdImportService
{
    public function __construct(
        protected TelegramChannelScraperService $scraper,
        protected AdAiParserService $aiParser,
        protected TelegramProductService $telegramService,
    ) {}

    /**
     * Run an import batch according to BotSetting.
     */
    public function importBatch(): array
    {
        $setting = BotSetting::current();

        if (!$setting->is_enabled) {
            return [
                'status' => 'disabled',
                'message' => 'Avto-e\'lonlar boti o\'chirilgan.',
                'imported_count' => 0,
            ];
        }

        if ($setting->today_imported_count >= $setting->daily_limit) {
            return [
                'status' => 'daily_limit_reached',
                'message' => "Bugungi kunlik limitga ({$setting->daily_limit}) yetildi.",
                'imported_count' => 0,
            ];
        }

        $remainingToday = $setting->daily_limit - $setting->today_imported_count;
        $targetCount = min($setting->hourly_limit, $remainingToday);

        $channels = $setting->getChannelList();
        if (empty($channels)) {
            return [
                'status' => 'no_channels',
                'message' => 'Manba kanallar ro\'yxati bo\'sh.',
                'imported_count' => 0,
            ];
        }

        // Shuffle channels so we fetch evenly across channels
        shuffle($channels);

        $botUser = User::getBotUser();
        $importedProducts = [];

        foreach ($channels as $channel) {
            if (count($importedProducts) >= $targetCount) {
                break;
            }

            $posts = $this->scraper->fetchChannelPosts($channel, 15);

            foreach ($posts as $post) {
                if (count($importedProducts) >= $targetCount) {
                    break;
                }

                $text = trim($post['text']);
                if (empty($text)) {
                    continue;
                }

                // Check post freshness: only allow posts from the last 24 hours (today)
                if (!empty($post['date'])) {
                    try {
                        $postDate = \Carbon\Carbon::parse($post['date']);
                        if ($postDate->lt(now()->subHours(24))) {
                            continue;
                        }
                    } catch (\Throwable) {
                        // ignore parse errors
                    }
                }

                // Check duplicate hash
                $sourceHash = md5($channel . '_' . $post['message_id'] . '_' . mb_substr($text, 0, 100));
                if (Product::where('source_hash', $sourceHash)->exists()) {
                    continue;
                }

                // Parse ad text with channel context for city/district resolution
                $parsed = $this->aiParser->parse($text, $setting, $channel);
                if (!$parsed || empty($parsed['is_valid'])) {
                    continue;
                }

                // Check phone duplicate: if same phone posted same title in last 5 days, skip
                if (!empty($parsed['phone'])) {
                    $recentDuplicate = Product::where('phone', $parsed['phone'])
                        ->where('created_at', '>=', now()->subDays(5))
                        ->where('title', 'like', mb_substr($parsed['title'], 0, 20) . '%')
                        ->exists();

                    if ($recentDuplicate) {
                        continue;
                    }
                }

                // Determine listing duration
                $category = Category::find($parsed['category_id']);
                $durationDays = $category?->listing_duration_days ?? 30;

                // Create Product
                $product = Product::create([
                    'title' => $parsed['title'] ?? 'E\'lon',
                    'description' => $parsed['description'] ?? $text,
                    'category_id' => $parsed['category_id'] ?? 1,
                    'district_id' => $parsed['district_id'] ?? null,
                    'user_id' => $botUser->id,
                    'phone' => $parsed['phone'] ?? null,
                    'price' => $parsed['price'] ?? null,
                    'price_type_id' => $parsed['price_type_id'] ?? 1,
                    'back_color_id' => 1,
                    'is_bot' => true,
                    'source_channel' => $channel,
                    'source_message_id' => $post['message_id'],
                    'source_hash' => $sourceHash,
                    'published_at' => now(),
                    'expires_at' => now()->addDays($durationDays),
                ]);

                // Download and attach images (up to 4 images), but omit for Jobs and Services
                if (!empty($post['images']) && !$this->isJobOrServiceCategory($category)) {
                    $this->attachImages($product, array_slice($post['images'], 0, 4));
                }

                $importedProducts[] = $product;
            }
        }

        $importedCount = count($importedProducts);

        // Update setting statistics
        $setting->increment('today_imported_count', $importedCount);
        $setting->update(['last_run_at' => now()]);

        // Decide on Telegram publication for this hourly run:
        // Only publish 1 single best ad if enabled and within active daytime hours
        $telegramPublishedProduct = null;
        if ($setting->telegram_post_enabled && !empty($importedProducts)) {
            $currentHour = (int) now('Asia/Tashkent')->format('G');

            if ($currentHour >= $setting->telegram_start_hour && $currentHour <= $setting->telegram_end_hour) {
                // Find best candidate: has image and has price
                $candidate = collect($importedProducts)->first(function ($prod) {
                    return $prod->images()->exists() && !empty($prod->price);
                }) ?? collect($importedProducts)->first(function ($prod) {
                    return $prod->images()->exists();
                }) ?? $importedProducts[0];

                try {
                    $this->telegramService->publish($candidate);
                    $telegramPublishedProduct = $candidate->id;
                    Log::info("Telegram kanalga avto-e'lon chiqarildi: Product #{$candidate->id}");
                } catch (\Throwable $e) {
                    Log::error("Telegram kanalga avto-e'lon chiqarishda xatolik: " . $e->getMessage());
                }
            } else {
                Log::info("Tungi rejim: Telegram kanalga e'lon yuborilmadi (Soat: {$currentHour}:00).");
            }
        }

        return [
            'status' => 'success',
            'imported_count' => $importedCount,
            'telegram_published_product_id' => $telegramPublishedProduct,
            'today_total' => $setting->fresh()->today_imported_count,
        ];
    }

    /**
     * Download and save images for a product.
     */
    protected function attachImages(Product $product, array $imageUrls): void
    {
        foreach ($imageUrls as $url) {
            try {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($url);

                if (!$response->successful()) {
                    continue;
                }

                $contents = $response->body();
                if (strlen($contents) < 1000) {
                    continue;
                }

                $filename = Str::uuid() . '.webp';
                $path = 'products/' . $filename;
                $destination = public_path('storage/' . $path);

                File::ensureDirectoryExists(dirname($destination));

                try {
                    ImageManager::usingDriver(Driver::class)
                        ->decode($contents)
                        ->scaleDown(1280, 1280)
                        ->encode(new WebpEncoder(quality: 82, strip: true))
                        ->save($destination);
                } catch (\Throwable) {
                    file_put_contents($destination, $contents);
                }

                ProductImage::create([
                    'product_id' => $product->id,
                    'src' => $path,
                    'crop_src' => null,
                    'crop_x' => 50,
                    'crop_y' => 50,
                    'crop_scale' => 1.0,
                ]);
            } catch (\Throwable $e) {
                Log::warning("E'lon rasmini yuklashda xatolik: {$url}: " . $e->getMessage());
            }
        }
    }

    /**
     * Check if category represents Jobs or Services where images should be omitted.
     */
    protected function isJobOrServiceCategory(?Category $category): bool
    {
        if (!$category) {
            return false;
        }

        // Job parent ID: 22, Service parent ID: 27
        $jobOrServiceParentIds = [22, 27];
        if (in_array($category->id, $jobOrServiceParentIds, true) || in_array($category->parent_id, $jobOrServiceParentIds, true)) {
            return true;
        }

        $lowerName = mb_strtolower($category->name);
        return str_contains($lowerName, 'ish') || str_contains($lowerName, 'xizmat') || str_contains($lowerName, 'vakansiya');
    }
}

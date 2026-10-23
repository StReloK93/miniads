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
                if (empty($text) || mb_strlen($text) < 15) {
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

                // 1. FILTER: Magazin, do'kon, aksiya, skidka va tijoriy reklamalarni AI ga bormasdan darhol to'xtatish
                if ($this->isCommercialOrPromo($text)) {
                    Log::info("Tijoriy/aksiya/do'kon e'loni o'tkazib yuborildi: " . mb_substr($text, 0, 45));
                    continue;
                }

                // 2. FILTER: Haqiqiy sotuvchi telefon raqami bo'lmagan xabarlarni o'tkazib yuborish
                $phone = $this->extractPhone($text);
                if (!$phone) {
                    continue;
                }

                // 3. FILTER: Content Fingerprint Hash (Barcha kanallar bo'yicha 100% bir xil matn dublikatini to'xtatish)
                $sourceHash = $this->generateContentFingerprint($text);
                if (Product::where('source_hash', $sourceHash)->exists()) {
                    continue;
                }

                // 4. FILTER: Telefon va semantik o'xshashlik (Bitta ma'nodagi e'lonni AI siz aniqlash)
                if ($this->isDuplicateByPhoneAndContent($phone, $text)) {
                    Log::info("Bitta ma'nodagi takroriy e'lon aniqlandi va o'tkazib yuborildi (Tel: {$phone})");
                    continue;
                }

                // Faqat barcha filtrlardan o'tgan haqiqiy va YANGI e'longina AI ga yuboriladi:
                $parsed = $this->aiParser->parse($text, $setting, $channel);
                if (!$parsed || empty($parsed['is_valid'])) {
                    continue;
                }

                // Double check if AI marked it as commercial/invalid
                if (!empty($parsed['phone'])) {
                    $phone = $parsed['phone'];
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
                    'phone' => $phone,
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

    /**
     * Check if text is commercial advertisement, shop/store promotion, aksiya, or business broadcast.
     */
    protected function isCommercialOrPromo(string $text): bool
    {
        $lower = mb_strtolower(AdAiParserService::cyrillicToLatin($text));

        $promoPatterns = [
            '/\baksiya\b/ui',
            '/\baktsiya\b/ui',
            '/\bakciya\b/ui',
            '/\bskidka\b/ui',
            '/\bchegirma\b/ui',
            '/\bchegirmalar\b/ui',
            '/\bsuper\s+taklif\b/ui',
            '/do[\'’`]?konimiz/ui',
            '/do[\'’`]?koni\b/ui',
            '/magazinimiz/ui',
            '/\bmagazin\b/ui',
            '/\bmarket\b/ui',
            '/\bshowroom\b/ui',
            '/\bshourum\b/ui',
            '/\bbutik\b/ui',
            '/\boptom\b/ui',
            '/\bulgurji\b/ui',
            '/assortiment/ui',
            '/katalog/ui',
            '/tanlov\s+keng/ui',
            '/bepul\s+dostavka/ui',
            '/yetkazib\s+berish\s+bepul/ui',
            '/dostavka\s+bepul/ui',
            '/buyurtma\s+(?:qiling|bering)/ui',
            '/nasiya\s+savdo/ui',
            '/muddatli\s+to[\'’`]?lov/ui',
            '/rassrochka/ui',
            '/o[\'’`]?quv\s+markaz/ui',
            '/kurslarimiz/ui',
            '/trening/ui',
            '/turizm/ui',
            '/\bumra\b/ui',
            '/\bhajj\b/ui',
            '/\bviza\b/ui',
            '/dorixona/ui',
            '/apteka/ui',
            '/klinika/ui',
            '/stomatolog/ui',
            '/restoran/ui',
            '/kafe\s+menyu/ui',
            '/kafeimiz/ui',
            '/reklama\s+uchun/ui',
            '/sponsor/ui',
            '/homiy/ui',
        ];

        foreach ($promoPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract primary seller phone number from ad text.
     */
    protected function extractPhone(string $text): ?string
    {
        if (preg_match('/(?:\+?998[\s-]?)?\(?([389]\d)\)?[\s-]?(\d{3})[\s-]?(\d{2})[\s-]?(\d{2})/', $text, $matches)) {
            return '998' . $matches[1] . $matches[2] . $matches[3] . $matches[4];
        }

        return null;
    }

    /**
     * Generate content fingerprint hash to prevent identical reposts across any channel.
     */
    protected function generateContentFingerprint(string $text): string
    {
        $clean = mb_strtolower(AdAiParserService::cyrillicToLatin($text));
        $clean = preg_replace('/https?:\/\/\S+/i', '', $clean);
        $clean = preg_replace('/@\w+/u', '', $clean);
        $clean = preg_replace('/(?:\+?998[\s-]?)?\(?\d{2}\)?[\s-]?\d{3}[\s-]?\d{2}[\s-]?\d{2}/', '', $clean);
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', '', $clean);

        return md5($clean);
    }

    /**
     * Check if this phone already posted an ad with identical or very similar meaning in the last 5 days.
     */
    protected function isDuplicateByPhoneAndContent(string $phone, string $newText): bool
    {
        $recentProducts = Product::where('phone', $phone)
            ->where('created_at', '>=', now()->subDays(5))
            ->get(['id', 'title', 'description']);

        if ($recentProducts->isEmpty()) {
            return false;
        }

        $cleanNew = mb_strtolower(AdAiParserService::cyrillicToLatin($newText));
        $cleanNew = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $cleanNew);

        preg_match_all('/\b\d{2,}\b/', $cleanNew, $newNumbers);
        $newNumList = array_unique($newNumbers[0] ?? []);

        foreach ($recentProducts as $prod) {
            $oldText = mb_strtolower(AdAiParserService::cyrillicToLatin($prod->description . ' ' . $prod->title));
            $cleanOld = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $oldText);

            // 1. Text similarity percentage
            similar_text($cleanNew, $cleanOld, $percent);
            if ($percent >= 45) {
                return true;
            }

            // 2. Overlapping key numbers (prices, years, specs) + partial similarity
            preg_match_all('/\b\d{2,}\b/', $cleanOld, $oldNumbers);
            $oldNumList = array_unique($oldNumbers[0] ?? []);
            $commonNums = array_intersect($newNumList, $oldNumList);

            if (count($commonNums) >= 2 && $percent >= 28) {
                return true;
            }
        }

        return false;
    }
}

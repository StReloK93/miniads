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
     * Guarantees aiming to import the exact targetCount (hourly_limit from admin panel).
     */
    public function importBatch(?int $customTarget = null, bool $force = false): array
    {
        $setting = BotSetting::current();

        if (!$setting->is_enabled && !$force) {
            return [
                'status' => 'disabled',
                'message' => 'Avto-e\'lonlar boti o\'chirilgan.',
                'imported_count' => 0,
            ];
        }

        // Qat'iy target soni: admin panelda berilgan hourly_limit (masalan 10 ta desangiz, aynan 10 ta)
        $targetCount = max(1, (int) ($customTarget ?: $setting->hourly_limit));

        if (!$force && $setting->today_imported_count >= $setting->daily_limit) {
            return [
                'status' => 'daily_limit_reached',
                'message' => "Bugungi kunlik limitga ({$setting->daily_limit}) yetildi.",
                'imported_count' => 0,
            ];
        }

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

        // Agar bitta aylanada targetCount ga (masalan 10 taga) yetmasa,
        // kanallardan chuqurroq (45 ta, so'ngra 90 tagacha) xabarlar olib, targetCount to'lguncha qidiriladi:
        $scrapePasses = [45, 90];

        foreach ($scrapePasses as $scrapeLimit) {
            if (count($importedProducts) >= $targetCount) {
                break;
            }

            foreach ($channels as $channel) {
                if (count($importedProducts) >= $targetCount) {
                    break;
                }

                $posts = $this->scraper->fetchChannelPosts($channel, $scrapeLimit);

                foreach ($posts as $post) {
                    if (count($importedProducts) >= $targetCount) {
                        break;
                    }

                    $messageId = $post['message_id'] ?? null;

                    // 0. CHECK: Ushbu kanal va xabar ID avval saqlanganmi? (Tezkor indeks orqali)
                    if ($messageId && Product::where('source_channel', $channel)->where('source_message_id', $messageId)->exists()) {
                        continue;
                    }

                    $text = trim($post['text'] ?? '');
                    if (empty($text) || mb_strlen($text) < 15) {
                        continue;
                    }

                    // 1. FRESHNESS: Oxirgi 7 kunga (1 haftalik yangi e'lonlarga) to'liq ruxsat beriladi
                    if (!empty($post['date'])) {
                        try {
                            $postDate = \Carbon\Carbon::parse($post['date']);
                            if ($postDate->lt(now()->subDays(7))) {
                                continue;
                            }
                        } catch (\Throwable) {
                            // ignore parse errors
                        }
                    }

                    // 2. FILTER: Aniq tijoriy/do'kon/reklama xabarlarni to'xtatish (mo'ljal so'zlariga tegmasdan)
                    if ($this->isCommercialOrPromo($text)) {
                        Log::info("Tijoriy/aksiya/reklama e'loni o'tkazib yuborildi: " . mb_substr($text, 0, 45));
                        continue;
                    }

                    // 3. FILTER: Aloqa vositasi (telefon raqam yoki Telegram @username)
                    $phone = $this->extractPhone($text);
                    $telegramUsername = $this->extractTelegramUsername($text);
                    if (!$phone && !$telegramUsername) {
                        continue;
                    }

                    // 4. FILTER: Content Fingerprint Hash (Barcha kanallar bo'yicha 100% bir xil matn dublikatini to'xtatish)
                    $sourceHash = $this->generateContentFingerprint($text);
                    if (Product::where('source_hash', $sourceHash)->exists()) {
                        continue;
                    }

                    // 5. FILTER: Telefon orqali aynan bir xil takroriy e'lonlarni tekshirish (o'xshashlik >= 75%)
                    if ($phone && $this->isDuplicateByPhoneAndContent($phone, $text)) {
                        Log::info("Bitta ma'nodagi takroriy e'lon o'tkazib yuborildi (Tel: {$phone})");
                        continue;
                    }

                    // E'lonni tahlil qilish (AI yoki fallback orqali):
                    $parsed = $this->aiParser->parse($text, $setting, $channel);
                    if (!$parsed || empty($parsed['is_valid'])) {
                        continue;
                    }

                    if (!empty($parsed['phone'])) {
                        $phone = $parsed['phone'];
                    }

                    // Agar telefon raqam topilmagan bo'lsa, username ni kontakt sifatida saqlaymiz
                    $contactPhone = $phone ?: ($telegramUsername ? '@' . $telegramUsername : null);

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
                        'phone' => $contactPhone,
                        'price' => $parsed['price'] ?? null,
                        'price_type_id' => $parsed['price_type_id'] ?? 1,
                        'back_color_id' => 1,
                        'is_bot' => true,
                        'source_channel' => $channel,
                        'source_message_id' => $messageId,
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
     * Note: "Mo'ljal" (market yonida, magazin oldida) and private seller discounts (skidkasi bor, kelishamiz) are NOT blocked.
     */
    protected function isCommercialOrPromo(string $text): bool
    {
        $lower = mb_strtolower(AdAiParserService::cyrillicToLatin($text));

        $strictPromoPatterns = [
            '/do[\'’`]?konimiz(?:ga|da|ning)/ui',
            '/bizning\s+do[\'’`]?kon/ui',
            '/magazinimiz(?:ga|da|ning)/ui',
            '/bizning\s+(?:filial|manzil|shourum|showroom)/ui',
            '/\boptom\s+sotamiz\b/ui',
            '/\bulgurji\s+narxda\b/ui',
            '/assortimentimiz/ui',
            '/katalogimiz/ui',
            '/tanlov\s+juda\s+keng/ui',
            '/bepul\s+dostavka/ui',
            '/yetkazib\s+berish\s+bepul/ui',
            '/dostavka\s+bepul/ui',
            '/nasiya\s+savdo/ui',
            '/muddatli\s+to[\'’`]?lov/ui',
            '/boshlang[\'’`]?ich\s+to[\'’`]?lovsiz/ui',
            '/rassrochka(?:ga|\s+bor)/ui',
            '/o[\'’`]?quv\s+markaz(?:imiz)?/ui',
            '/kurslarimiz(?:ga|da)/ui',
            '/trening(?:imiz)?/ui',
            '/turizm\s+xizmat/ui',
            '/\bumra\s+(?:ziyorat|safar|viza|paket)/ui',
            '/\bhajj\s+safar/ui',
            '/\bviza\s+xizmat/ui',
            '/reklama\s+uchun\s+admin/ui',
            '/reklama\s+berish\s+uchun/ui',
            '/kanalimizga\s+a[\'’`]?zo\s+bo[\'’`]?ling/ui',
            '/obuna\s+bo[\'’`]?ling/ui',
            '/kanalga\s+ulaning/ui',
            '/\bsponsor\b/ui',
            '/\bhomiy\b/ui',
        ];

        foreach ($strictPromoPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract primary seller phone number from ad text.
     * Supports all Uzbek formats: 935504433, +998935504433, 93 550 44 33, (93) 550-44-33, 8935504433, etc.
     */
    protected function extractPhone(string $text): ?string
    {
        $patterns = [
            '/(?<!\d)(?:\+?998[\s.-]?)?\(?([3789]\d)\)?[\s.-]?(\d{3})[\s.-]?(\d{2})[\s.-]?(\d{2})(?!\d)/u',
            '/(?<!\d)(?:8[\s.-]?)?\(?([3789]\d)\)?[\s.-]?(\d{3})[\s.-]?(\d{2})[\s.-]?(\d{2})(?!\d)/u',
            '/(?<!\d)([3789]\d)(\d{7})(?!\d)/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $code = $matches[1];
                $rest = isset($matches[4]) ? ($matches[2] . $matches[3] . $matches[4]) : $matches[2];
                $cleanRest = preg_replace('/\D/', '', $rest);
                if (strlen($cleanRest) === 7) {
                    return '998' . $code . $cleanRest;
                }
            }
        }

        // Fallback: search for any phone-like sequence
        if (preg_match_all('/(?:\+?998|8)?[\s().-]*[3789]\d[\s().-]*\d{3}[\s().-]*\d{2}[\s().-]*\d{2}/u', $text, $all)) {
            foreach ($all[0] as $raw) {
                $digits = preg_replace('/\D/', '', $raw);
                if (strlen($digits) === 9) {
                    return '998' . $digits;
                }
                if (strlen($digits) === 10 && str_starts_with($digits, '8')) {
                    return '998' . substr($digits, 1);
                }
                if (strlen($digits) === 12 && str_starts_with($digits, '998')) {
                    return $digits;
                }
            }
        }

        return null;
    }

    /**
     * Extract Telegram username if seller left contact as @username instead of phone.
     */
    protected function extractTelegramUsername(string $text): ?string
    {
        if (preg_match('/@([a-zA-Z0-9_]{4,32})/u', $text, $matches)) {
            $username = $matches[1];
            $ignored = ['admin', 'elon_admin', 'aloqa_bot', 'joinchat', 'share'];
            if (!in_array(strtolower($username), $ignored, true)) {
                return $username;
            }
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
        $clean = preg_replace('/(?:\+?998|8)?[\s().-]*[3789]\d[\s().-]*\d{3}[\s().-]*\d{2}[\s().-]*\d{2}/u', '', $clean);
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', '', $clean);

        return md5($clean);
    }

    /**
     * Check if this phone already posted an ad with identical or very similar meaning in the last 3 days (>= 75%).
     */
    protected function isDuplicateByPhoneAndContent(string $phone, string $newText): bool
    {
        $recentProducts = Product::where('phone', $phone)
            ->where('created_at', '>=', now()->subDays(3))
            ->get(['id', 'title', 'description']);

        if ($recentProducts->isEmpty()) {
            return false;
        }

        $cleanNew = mb_strtolower(AdAiParserService::cyrillicToLatin($newText));
        $cleanNew = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $cleanNew);

        foreach ($recentProducts as $prod) {
            $oldText = mb_strtolower(AdAiParserService::cyrillicToLatin($prod->description . ' ' . $prod->title));
            $cleanOld = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $oldText);

            // Faqat haqiqiy takroriy e'lon bo'lsa (matn o'xshashligi 75% dan yuqori)
            similar_text($cleanNew, $cleanOld, $percent);
            if ($percent >= 75) {
                return true;
            }
        }

        return false;
    }
}

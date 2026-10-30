<?php

namespace App\Services\Bot;

use App\Models\BotSeenPost;
use App\Models\BotSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\TelegramProductService;
use Illuminate\Http\UploadedFile;
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
        protected AdPrefilter $prefilter,
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
        $aiStoppedReason = null;

        // Agar bitta aylanada targetCount ga (masalan 10 taga) yetmasa,
        // kanallardan chuqurroq (45 ta, so'ngra 90 tagacha) xabarlar olib, targetCount to'lguncha qidiriladi.
        // Ko'rilgan postlar bot_seen_posts'da turadi, shuning uchun ikkinchi aylana ularni qayta tahlil qilmaydi.
        $scrapePasses = [45, 90];

        foreach ($scrapePasses as $scrapeLimit) {
            if (count($importedProducts) >= $targetCount || $aiStoppedReason) {
                break;
            }

            foreach ($channels as $channel) {
                if (count($importedProducts) >= $targetCount || $aiStoppedReason) {
                    break;
                }

                $posts = $this->scraper->fetchChannelPosts($channel, $scrapeLimit);
                $seenIds = $this->seenMessageIds($channel, $posts);

                foreach ($posts as $post) {
                    if (count($importedProducts) >= $targetCount) {
                        break;
                    }

                    $messageId = (int) ($post['message_id'] ?? 0);
                    if (!$messageId || isset($seenIds[$messageId])) {
                        continue;
                    }

                    try {
                        [$status, $reason, $product] = $this->processPost($post, $channel, $setting, $botUser);
                    } catch (AiUnavailableException $e) {
                        // AI ishlamayapti yoki limit tugadi: post belgilanmaydi (keyingi safar qayta ko'riladi), import to'xtaydi
                        $aiStoppedReason = $e->getMessage();
                        Log::warning("Bot import to'xtatildi: {$aiStoppedReason}");
                        break;
                    }

                    BotSeenPost::mark($channel, $messageId, $status, $reason);
                    $seenIds[$messageId] = true;

                    if ($product) {
                        $importedProducts[] = $product;
                    }
                }
            }
        }

        // 30 kundan eski yozuvlar kerak emas (7 kundan eski postlar baribir o'tkazib yuboriladi)
        BotSeenPost::where('created_at', '<', now()->subDays(30))->delete();

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

        $usage = AdAiParserService::todayUsage();

        return [
            'status' => $aiStoppedReason ? 'ai_stopped' : 'success',
            'message' => $aiStoppedReason
                ? "{$importedCount} ta e'lon qo'shildi, keyin AI to'xtadi: {$aiStoppedReason}"
                : "{$importedCount} ta e'lon qo'shildi. Bugun AI: {$usage['requests']} so'rov, {$usage['tokens']} token.",
            'imported_count' => $importedCount,
            'telegram_published_product_id' => $telegramPublishedProduct,
            'today_total' => $setting->fresh()->today_imported_count,
            'ai_usage' => $usage,
        ];
    }

    /**
     * Shu kanal postlaridan qaysilari avval ko'rilgan (yoki import qilingan, jumladan keyin o'chirilgan) — bitta so'rovda.
     */
    protected function seenMessageIds(string $channel, array $posts): array
    {
        $ids = array_values(array_filter(array_map(fn($p) => (int) ($p['message_id'] ?? 0), $posts)));
        if ($ids === []) {
            return [];
        }

        $seen = BotSeenPost::where('channel', $channel)->whereIn('message_id', $ids)->pluck('message_id');
        // bot_seen_posts paydo bo'lishidan oldin import qilinganlar; admin o'chirganlari ham qayta kirmasin
        $imported = Product::withTrashed()->where('source_channel', $channel)->whereIn('source_message_id', $ids)->pluck('source_message_id');

        return $seen->merge($imported)->mapWithKeys(fn($id) => [(int) $id => true])->all();
    }

    /**
     * Bitta postni tekshiradi. Arzon filtrlar avval, AI eng oxirida.
     *
     * @return array{0: string, 1: ?string, 2: ?Product} [status, sabab, yaratilgan e'lon]
     */
    protected function processPost(array $post, string $channel, BotSetting $setting, User $botUser): array
    {
        $text = trim($post['text'] ?? '');
        if (mb_strlen($text) < 15) {
            return ['filtered', 'matn qisqa', null];
        }

        // 1. FRESHNESS: Oxirgi 7 kunga (1 haftalik yangi e'lonlarga) to'liq ruxsat beriladi
        if (!empty($post['date'])) {
            try {
                if (\Carbon\Carbon::parse($post['date'])->lt(now()->subDays(7))) {
                    return ['old', null, null];
                }
            } catch (\Throwable) {
                // ignore parse errors
            }
        }

        // 2. FILTER: Aniq tijoriy/do'kon/reklama xabarlarni to'xtatish (mo'ljal so'zlariga tegmasdan)
        if ($this->isCommercialOrPromo($text)) {
            return ['filtered', 'tijoriy reklama', null];
        }

        // 3. FILTER: Aloqa vositasi. Kanalning o'z imzosi (@KanalNomi, t.me/...) kontakt hisoblanmaydi
        $contactText = $this->prefilter->stripChannelNoise($text, $channel);
        $phone = $this->extractPhone($contactText);
        $telegramUsername = $this->extractTelegramUsername($contactText);
        if (!$phone && !$telegramUsername) {
            return ['filtered', 'kontakt yo\'q', null];
        }

        // 4. FILTER: Yangilik, tabrik va taklifsiz uzun matnlar — AI'ga yuborilmaydi
        if ($reason = $this->prefilter->rejectReason($text)) {
            return ['filtered', $reason, null];
        }

        // 5. FILTER: Content Fingerprint Hash (Barcha kanallar bo'yicha 100% bir xil matn dublikatini to'xtatish)
        $sourceHash = $this->generateContentFingerprint($text);
        if (Product::withTrashed()->where('source_hash', $sourceHash)->exists()) {
            return ['duplicate', 'bir xil matn', null];
        }

        // 6. FILTER: Telefon orqali aynan bir xil takroriy e'lonlarni tekshirish (o'xshashlik >= 75%)
        if ($phone && $this->isDuplicateByPhoneAndContent($phone, $text)) {
            return ['duplicate', "o'xshash e'lon (tel: {$phone})", null];
        }

        // 7. AI: haqiqiy e'lonmi va uni tozalash
        $parsed = $this->aiParser->parse($text, $setting, $channel);
        if (!$parsed || empty($parsed['is_valid'])) {
            return ['rejected', 'AI: e\'lon emas', null];
        }

        if (!empty($parsed['phone'])) {
            $phone = $parsed['phone'];
        }

        // Agar telefon raqam topilmagan bo'lsa, username ni kontakt sifatida saqlaymiz
        $contactPhone = $phone ?: ($telegramUsername ? '@' . $telegramUsername : null);

        $product = $this->createProduct($post, $channel, $parsed, $contactPhone, $sourceHash, $botUser);
        $category = $product->category;

        // Rasmlar (4 tagacha) — faqat rasmi bor kategoriyalarda
        if (!empty($post['images']) && $this->acceptsImages($category)) {
            $this->attachImages($product, array_slice($post['images'], 0, 4));
        }

        // Kanallardan kelgan e'lonlar ham taqiqlangan so'zlarga tekshiriladi
        try {
            app(\App\Services\ModerationService::class)->scan($product);
        } catch (\Throwable $e) {
            Log::warning("Moderatsiya tekshiruvi xatosi: {$e->getMessage()}", ['product_id' => $product->id]);
        }

        return ['imported', null, $product];
    }

    /**
     * Admin havola orqali o'zi tanlagan post (t.me/kanal/123): hech qanday filtr, moderatsiya va "e'lon emas"
     * rad etishi yo'q — e'lon albatta joylanadi. AI faqat sarlavha, tavsif, narx, kategoriya va hududni ajratadi;
     * rasmlar olinadi (kategoriyada rasm o'chirilmagan bo'lsa). Bir post ikki marta qo'shilmaydi.
     *
     * @return array{status: string, message: string, product?: Product, parsed_by?: string}
     */
    public function importChosen(string $url, bool $publish = false): array
    {
        $ref = TelegramChannelScraperService::parsePostUrl($url);
        if (!$ref) {
            return ['status' => 'invalid', 'message' => "Havola noto'g'ri. Ochiq kanal posti kerak: t.me/kanal/123"];
        }
        [$channel, $messageId] = $ref;

        $existing = Product::where('source_channel', $channel)->where('source_message_id', $messageId)->first();
        if ($existing) {
            return ['status' => 'exists', 'message' => "Bu post allaqachon qo'shilgan (#{$existing->id}).", 'product' => $existing];
        }

        $post = $this->scraper->fetchPost($channel, $messageId);
        $text = trim($post['text'] ?? '');
        if (!$post || $text === '') {
            return ['status' => 'not_found', 'message' => "Post topilmadi yoki matni yo'q (kanal yopiq yoki post o'chirilgan)."];
        }

        $result = $this->storeChosen($post, $channel, $post['images'] ?? [], $publish);
        BotSeenPost::mark($channel, $messageId, 'imported', 'admin havolasi');

        return $result;
    }

    /**
     * Admin guruhdan nusxalab joylagan matn (va o'zi qo'ygan rasmlar) — importChosen kabi filtrsiz, albatta joylanadi.
     * Aynan shu matn avval qo'shilgan bo'lsa, qayta qo'shilmaydi.
     *
     * @param  UploadedFile[]  $images
     * @return array{status: string, message: string, product?: Product, parsed_by?: string}
     */
    public function importText(string $text, array $images = [], bool $publish = false): array
    {
        $text = trim($text);
        if (mb_strlen($text) < 10) {
            return ['status' => 'invalid', 'message' => "Matn juda qisqa."];
        }

        $existing = Product::where('source_hash', $this->generateContentFingerprint($text))->first();
        if ($existing) {
            return ['status' => 'exists', 'message' => "Bu matn allaqachon qo'shilgan (#{$existing->id}).", 'product' => $existing];
        }

        return $this->storeChosen(['text' => $text, 'message_id' => null], null, $images, $publish);
    }

    /**
     * @param  array<string|UploadedFile>  $images  post rasmlari havolasi yoki admin yuklagan fayllar
     */
    protected function storeChosen(array $post, ?string $channel, array $images, bool $publish): array
    {
        $text = trim($post['text']);
        $parsed = $this->aiParser->parseChosen($text, BotSetting::current(), $channel);

        $contactText = $this->prefilter->stripChannelNoise($text, $channel);
        $username = $this->extractTelegramUsername($contactText);
        $contact = ($parsed['phone'] ?? null) ?: $this->extractPhone($contactText) ?: ($username ? '@' . $username : null);

        $product = $this->createProduct($post, $channel, $parsed, $contact, $this->generateContentFingerprint($text), User::getBotUser());
        if ($images && $this->acceptsImages($product->category)) {
            $this->attachImages($product, array_slice($images, 0, 10));
        }

        if ($publish) {
            try {
                $this->telegramService->publish($product);
            } catch (\Throwable $e) {
                Log::error("Tanlangan e'lonni kanalga chiqarib bo'lmadi: {$e->getMessage()}", ['product_id' => $product->id]);
            }
        }

        return [
            'status' => 'imported',
            'message' => "E'lon qo'shildi (#{$product->id}).",
            'product' => $product->load('images', 'category', 'district'),
            'parsed_by' => $parsed['parsed_by'] ?? 'ai',
        ];
    }

    protected function createProduct(array $post, ?string $channel, array $parsed, ?string $contactPhone, string $sourceHash, User $botUser): Product
    {
        $category = Category::find($parsed['category_id'] ?? null);
        $durationDays = $category?->listing_duration_days ?? 30;

        return Product::create([
            'title' => $parsed['title'] ?? 'E\'lon',
            'description' => $parsed['description'] ?? trim($post['text'] ?? ''),
            'category_id' => $parsed['category_id'] ?? 1,
            'district_id' => $parsed['district_id'] ?? null,
            'user_id' => $botUser->id,
            'phone' => $contactPhone,
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
    }

    /**
     * Download and save images for a product.
     *
     * @param  array<string|UploadedFile>  $images
     */
    protected function attachImages(Product $product, array $images): void
    {
        foreach ($images as $image) {
            $url = $image instanceof UploadedFile ? $image->getClientOriginalName() : $image;

            try {
                if ($image instanceof UploadedFile) {
                    $contents = (string) $image->get();
                } else {
                    $response = Http::timeout(8)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                        ->get($image);

                    if (!$response->successful()) {
                        continue;
                    }

                    $contents = $response->body();
                }

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
                    'thumb_src' => \App\Support\ProductThumbnail::make($path),
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
     * Ilovadagi kabi: kategoriyada rasm o'chirilgan bo'lsa (with_image, admin panel → Kategoriyalar) — rasm olinmaydi.
     * Hozir: Ish (Doimiy, Kunlik, Masofaviy ish, Xizmat takliflari) va Ta'lim (Kurslar, Repetitor, Online darslar).
     */
    protected function acceptsImages(?Category $category): bool
    {
        return !$category || $category->with_image !== false;
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

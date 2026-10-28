<?php

namespace App\Services\Bot;

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\District;
use App\Models\PriceType;
use App\Services\ErrorAlert;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdAiParserService
{
    /**
     * Parse raw ad text into structured product data.
     *
     * null — post e'lon emas (rad etildi). AI javob bera olmasa AiUnavailableException tashlanadi.
     */
    public function parse(string $rawText, BotSetting $setting, ?string $channel = null): ?array
    {
        $rawText = trim($rawText);
        if (mb_strlen($rawText) < 15) {
            return null;
        }

        // Basic filter: must have a phone number or telegram @username
        $hasContact = preg_match('/(?:\+?998|8)?[\s().-]*[3789]\d[\s().-]*\d{3}[\s().-]*\d{2}[\s().-]*\d{2}/u', $rawText)
            || preg_match('/(?<!\d)[3789]\d\d{7}(?!\d)/u', $rawText)
            || preg_match('/@[a-zA-Z0-9_]{4,}/', $rawText)
            || preg_match('/(?:tel|aloqa|nomer|telefon|telegram|lichka|murojaat)\s*[:\-]?\s*[\d+@]/ui', $rawText);

        if (!$hasContact) {
            return null;
        }

        // Filter out obvious spam / advertisements
        if ($this->isObviousSpam($rawText)) {
            return null;
        }

        $provider = $setting->ai_provider ?: 'gemini';
        $apiKey = $setting->ai_api_key ?: config($provider === 'openai' ? 'services.openai.api_key' : 'services.gemini.api_key');

        // AI yo'q bo'lsa (regex rejimi yoki kalit yo'q): qoidali parser, lekin faqat taklif niyati aniq postlar uchun
        if ($provider === 'regex' || empty($apiKey)) {
            if (!app(AdPrefilter::class)->hasOfferIntent($rawText)) {
                return null;
            }

            return $this->normalizeParsedData($this->fallbackParse($rawText, $channel), $rawText, $channel);
        }

        $this->ensureWithinDailyTokenLimit();

        $aiResult = $provider === 'openai'
            ? $this->callOpenAi($rawText, $apiKey, $channel)
            : $this->callGemini($rawText, $apiKey, $channel);

        // AI "e'lon emas" desa — shu qaror qabul qilinadi (avval bu holatda fallback e'lonni baribir saqlab yuborardi)
        if (empty($aiResult['is_valid'])) {
            Log::info("AI e'lonni rad etdi (" . ($aiResult['reason'] ?? 'sababsiz') . '): ' . mb_substr($rawText, 0, 60));

            return null;
        }

        return $this->normalizeParsedData($aiResult, $rawText, $channel);
    }

    /**
     * Bugun AI'ga sarflangan tokenlar.
     */
    public static function todayUsage(): array
    {
        $day = now()->toDateString();

        return [
            'tokens' => (int) Cache::get("ai_usage:{$day}:tokens", 0),
            'requests' => (int) Cache::get("ai_usage:{$day}:requests", 0),
            'limit' => (int) config('services.ai_daily_token_limit'),
        ];
    }

    private function ensureWithinDailyTokenLimit(): void
    {
        $usage = self::todayUsage();

        if ($usage['limit'] > 0 && $usage['tokens'] >= $usage['limit']) {
            throw new AiUnavailableException("AI kunlik token limiti ({$usage['limit']}) tugadi.");
        }
    }

    private function recordUsage(int $tokens): void
    {
        $day = now()->toDateString();
        $ttl = now()->addDays(3);

        Cache::add("ai_usage:{$day}:tokens", 0, $ttl);
        Cache::add("ai_usage:{$day}:requests", 0, $ttl);
        Cache::increment("ai_usage:{$day}:tokens", $tokens);
        Cache::increment("ai_usage:{$day}:requests");
    }

    /**
     * Check if text contains spam keywords.
     */
    private function isObviousSpam(string $text): bool
    {
        $spamKeywords = [
            '1xbet', 'melbet', 'mostbet', 'linebet', 'stavka',
            'pul ishlash', 'daromad olish', 'kuniga 100$', 'investitsiya',
            'reklama berish uchun', 'admin ga murojaat', 'kanalimizga a\'zo',
            'obuna bo\'ling', 'kanal sotiladi', 'kanalga ulaning', 'porn', 'intim',
        ];

        $lower = mb_strtolower($text);
        foreach ($spamKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * O'zgarmas ko'rsatmalar. Har bir so'rovda bir xil bo'lgani uchun Gemini ularni avtomatik keshlaydi (arzonroq).
     */
    private function systemPrompt(): string
    {
        $categoriesContext = $this->getCategoriesContext();
        $districtsContext = $this->getDistrictsContext();
        $boshqaId = Cache::remember('boshqa_category_id', 3600, fn() => Category::where('name', 'like', '%Boshqa%')->value('id')) ?? 55;

        return <<<PROMPT
Sen Navoiy viloyati e'lonlar platformasi uchun Telegram postlarni tekshiruvchi va tahrirlovchi assistentsan. Javob FAQAT JSON.

1-QADAM. Post haqiqiy e'lonmi?
Haqiqiy e'lon: muallif biror narsani SOTMOQCHI, SOTIB OLMOQCHI, IJARAGA BERMOQCHI yoki OLMOQCHI, ISH TAKLIF QILYAPTI yoki IZLAYAPTI, yoki XIZMAT ko'rsatyapti, va u bilan bog'lanish mumkin (telefon yoki @username).
Masalan: avtomobil, uy-joy, ijara, elektronika, telefon, maishiy texnika, mebel, kiyim, chorva, xizmatlar, vakansiya.
"Mo'ljal: Korzinka yonida", "magazin oldida", "kelishamiz", "skidka bor" kabi jumlalar rad etishga sabab EMAS.

E'lon EMAS (shunda faqat {"is_valid": false, "reason": "..."} qaytar):
- yangilik, rasmiy xabar, hodisa, ob-havo;
- tabrik, bayram, tadbir yoki yig'ilish haqida xabar;
- kanal reklamasi, obunaga chaqirish, konkurs, so'rovnoma;
- do'kon yoki kompaniyaning ommaviy reklamasi (aksiya, katalog, filiallar);
- kazino, stavka, piramida, intim;
- savol, suhbat, fikr yoki shunchaki ma'lumot.
reason qiymati: "yangilik", "tabrik", "reklama", "spam", "suhbat" yoki "boshqa".
Sotish/olish/ijara/ish/xizmat niyati aniq ko'rinmasa — is_valid: false.

2-QADAM. Haqiqiy e'lon bo'lsa, maydonlarni to'ldir:
- Til: title va description FAQAT o'zbek lotin alifbosida. Kirillni lotinga o'gir ("сотилади" -> "sotiladi", "кўчаси" -> "ko'chasi"), ruscha so'zlarni o'zbekchaga ("ремонт" -> "ta'mir", "этаж" -> "qavat", "ориентир" -> "mo'ljal", "угловой" -> "burchakdagi").
- title: 50 belgigacha, aniq va jozibador ("2 xonali kvartira sotiladi", "Spark 2019 mexanika oq rang"). "AKSIYA", "DIQQAT", kanal nomi yoki telefon qo'yilmaydi.
- description: emoji yo'q, faqat "•" belgisi. Bo'limlar orasida bo'sh qator:
  1) 1-2 gaplik qisqa tavsif;
  2) asosiy parametrlar ("• Holati: a'lo", "• Rangi: oq");
  3) "• Manzil: ..." (postda bo'lsa);
  4) "• Narxi: ..." (postda bo'lsa);
  5) "• Bog'lanish uchun: ..." — faqat telefon yoki @username.
  Begona kanal havolalari (@kanal, t.me/...) olib tashlanadi. Postda yo'q ma'lumotni o'ylab topma.
- price: faqat raqam yoki null. currency: "UZS", "USD" yoki null.
- phone: "998XXXXXXXXX" formatida yoki null.
- category_id: ro'yxatdagi eng mos ID. Aralash yoki noaniq bo'lsa {$boshqaId} ("Boshqa").
- district_id: matnda shahar bo'lsa o'sha, bo'lmasa manba kanal hududi.

Kategoriyalar: {$categoriesContext}

Shahar/tumanlar: {$districtsContext}

Haqiqiy e'lon uchun JSON:
{"is_valid": true, "title": "...", "description": "...", "price": 12000000, "currency": "UZS", "phone": "998901234567", "category_id": 12, "district_id": 1}
PROMPT;
    }

    /**
     * Har bir postga xos qism (kanal, hudud, matn) — keshlanadigan ko'rsatmalardan keyin yuboriladi.
     */
    private function userPrompt(string $rawText, ?string $channel): string
    {
        $prompt = '';

        if (!empty($channel)) {
            $prompt .= "Manba kanal: @{$channel}\n";
        }

        $detectedDistrictId = $this->guessDistrictId($rawText, $channel);
        if ($detectedDistrictId) {
            $districts = Cache::remember('districts_list', 3600, fn() => District::all(['id', 'name']));
            $district = $districts->firstWhere('id', $detectedDistrictId);
            if ($district) {
                $prompt .= "Aniq hudud: {$district->name} (ID: {$detectedDistrictId}). district_id shu bo'lishi SHART.\n";
            }
        }

        return $prompt . "Post matni:\n" . $rawText;
    }

    /**
     * Faqat vaqtinchalik xatolarda qayta urinadi (tarmoq, 429, 5xx). 4xx xatoni qayta yuborish — bekorga pul va vaqt.
     */
    private function shouldRetry(\Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        return $e instanceof RequestException
            && ($e->response->status() === 429 || $e->response->serverError());
    }

    private function decodeJson(?string $content): ?array
    {
        if (!$content) {
            return null;
        }

        $cleanJson = trim($content);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $cleanJson, $matches)) {
            $cleanJson = trim($matches[1]);
        }

        $decoded = json_decode($cleanJson, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function failed(string $provider, string $message): never
    {
        Log::error("{$provider} API xatolik: {$message}");
        $this->notifyTelegramFailure($provider, $message);

        throw new AiUnavailableException("{$provider}: {$message}");
    }

    /**
     * Call Google Gemini API. Bitta aniq model, fikrlash (thinking) minimal — bu vazifa uchun keragi yo'q va qimmat.
     */
    private function callGemini(string $rawText, string $apiKey, ?string $channel = null): array
    {
        $model = config('services.gemini.model');

        $generationConfig = [
            'responseMimeType' => 'application/json',
            'temperature' => 0.2,
        ];
        if (filled(config('services.gemini.thinking_level'))) {
            $generationConfig['thinkingConfig'] = ['thinkingLevel' => config('services.gemini.thinking_level')];
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(60)
                ->retry(2, 2000, fn(\Throwable $e) => $this->shouldRetry($e), throw: false)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'systemInstruction' => ['parts' => [['text' => $this->systemPrompt()]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => $this->userPrompt($rawText, $channel)]]]],
                    'generationConfig' => $generationConfig,
                ]);
        } catch (\Throwable $e) {
            $this->failed("Gemini ({$model})", $e->getMessage());
        }

        if (!$response->successful()) {
            $this->failed("Gemini ({$model})", (string) ($response->json('error.message') ?: $response->body()));
        }

        $this->recordUsage((int) $response->json('usageMetadata.totalTokenCount', 0));

        $decoded = $this->decodeJson($response->json('candidates.0.content.parts.0.text'));
        if ($decoded === null) {
            $this->failed("Gemini ({$model})", "JSON bo'lmagan javob: " . Str::limit((string) $response->json('candidates.0.content.parts.0.text'), 200));
        }

        return $decoded;
    }

    /**
     * Call OpenAI API.
     */
    private function callOpenAi(string $rawText, string $apiKey, ?string $channel = null): array
    {
        $model = config('services.openai.model');

        try {
            /** @var Response $response */
            $response = Http::timeout(60)
                ->retry(2, 2000, fn(\Throwable $e) => $this->shouldRetry($e), throw: false)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $this->userPrompt($rawText, $channel)],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.1,
                ]);
        } catch (\Throwable $e) {
            $this->failed("OpenAI ({$model})", $e->getMessage());
        }

        if (!$response->successful()) {
            $this->failed("OpenAI ({$model})", (string) ($response->json('error.message') ?: $response->body()));
        }

        $this->recordUsage((int) $response->json('usage.total_tokens', 0));

        $decoded = $this->decodeJson($response->json('choices.0.message.content'));
        if ($decoded === null) {
            $this->failed("OpenAI ({$model})", "JSON bo'lmagan javob");
        }

        return $decoded;
    }

    /**
     * Send an alert message to Telegram when AI service fails.
     */
    private function notifyTelegramFailure(string $provider, string $errorMessage): void
    {
        // Adminlarning o'ziga shaxsan (kanalga emas); bir xil xato 10 daqiqada bir marta
        ErrorAlert::send(
            "ai:{$provider}:{$errorMessage}",
            "AI e'lon tahlilida xatolik",
            [
                'Provayder' => $provider,
                'Xato' => Str::limit(strip_tags($errorMessage), 350),
                'Eslatma' => "Import to'xtatildi. Postlar keyingi importda qayta tahlil qilinadi.",
            ],
        );
    }

    /**
     * Fallback rule-based parsing when AI is not configured or fails.
     */
    public function fallbackParse(string $rawText, ?string $channel = null): array
    {
        // 1. Phone extraction (supports 935504433, +998935504433, 93 550 44 33, etc.)
        $phone = null;
        $patterns = [
            '/(?<!\d)(?:\+?998[\s.-]?)?\(?([3789]\d)\)?[\s.-]?(\d{3})[\s.-]?(\d{2})[\s.-]?(\d{2})(?!\d)/u',
            '/(?<!\d)(?:8[\s.-]?)?\(?([3789]\d)\)?[\s.-]?(\d{3})[\s.-]?(\d{2})[\s.-]?(\d{2})(?!\d)/u',
            '/(?<!\d)([3789]\d)(\d{7})(?!\d)/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $rawText, $matches)) {
                $code = $matches[1];
                $rest = isset($matches[4]) ? ($matches[2] . $matches[3] . $matches[4]) : $matches[2];
                $cleanRest = preg_replace('/\D/', '', $rest);
                if (strlen($cleanRest) === 7) {
                    $phone = '998' . $code . $cleanRest;
                    break;
                }
            }
        }

        if (!$phone && preg_match('/@([a-zA-Z0-9_]{4,})/', $rawText, $userMatch)) {
            $phone = '@' . $userMatch[1];
        }

        // 2. Price extraction
        $price = null;
        $currency = 'UZS';

        if (preg_match('/(\d+(?:[\s.,]\d+)*)\s*(\$|usd|dollar)/ui', $rawText, $priceMatch)) {
            $cleaned = preg_replace('/[^\d]/', '', $priceMatch[1]);
            $price = !empty($cleaned) ? (int) $cleaned : null;
            $currency = 'USD';
        } elseif (preg_match('/(\d+(?:[\s.,]\d+)*)\s*(?:so[\'’`]?m|sum|ming|mln)/ui', $rawText, $priceMatch)) {
            $cleaned = preg_replace('/[^\d]/', '', $priceMatch[1]);
            $price = !empty($cleaned) ? (int) $cleaned : null;
            $currency = 'UZS';
        }

        // 3. Title extraction (first non-empty, non-emoji line)
        $lines = preg_split('/[\r\n]+/', $rawText);
        $title = '';
        foreach ($lines as $line) {
            $cleanLine = trim(preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $line));
            if (mb_strlen($cleanLine) >= 6) {
                $title = mb_substr($cleanLine, 0, 60);
                break;
            }
        }
        if (empty($title)) {
            $title = mb_substr(trim(preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $rawText)), 0, 50);
        }

        // 4. Category matching by keywords
        $categoryId = $this->guessCategoryId($rawText);

        // 5. District matching by keywords or channel fallback
        $districtId = $this->guessDistrictId($rawText, $channel);

        $cleanTitle = self::cleanTitle($title);
        $cleanDesc = self::cleanDescription($rawText);

        return [
            'is_valid' => true,
            'title' => $cleanTitle ?: 'E\'lon',
            'description' => $cleanDesc ?: $rawText,
            'price' => $price,
            'currency' => $currency,
            'phone' => $phone,
            'category_id' => $categoryId,
            'district_id' => $districtId,
        ];
    }

    /**
     * Guess category by keywords.
     */
    private function guessCategoryId(string $text): int
    {
        $lower = mb_strtolower($text);

        // Map keywords to category types
        $rules = [
            'telefon' => ['iphone', 'samsung', 'redmi', 'xiaomi', 'telefon', 'smartfon', 'pro max', 'galaxy', 'honor'],
            'maishiy texnika' => ['kir yuvish', 'muzlatgich', 'xolodilnik', 'konditsioner', 'gaz plita', 'changyutgich', 'televizor', 'smart tv', 'artel'],
            'noutbuk' => ['noutbuk', 'kompyuter', 'macbook', 'asus', 'lenovo', 'acer'],
            'kvartira' => ['kvartira', 'xonali', 'etaj', 'dom', 'ijara kvartira', 'kvartira sotiladi'],
            'hovli' => ['hovli', 'uchastka', 'kottedj', 'yer joy'],
            'yengil avto' => ['spark', 'cobalt', 'nexia', 'gentra', 'lacetti', 'matiz', 'damas', 'tracker', 'monza', 'onix', 'bmw', 'mercedes', 'mashina', 'moshina', 'avtomobil', 'inomarka'],
            'ish' => ['ishga taklif', 'vakansiya', 'ishchi kerak', 'oylik', 'maosh', 'talab qilinadi'],
            'chorva' => ['sigir', 'buzoq', 'qoramol', 'qo\'y', 'qozi', 'echki', 'ot', 'tovuq', 'zotdor'],
            'mebel' => ['divan', 'krovat', 'shkaf', 'stol', 'stul', 'oshxona mebel', 'mebel'],
        ];

        // Retrieve existing leaf categories
        $categories = Cache::remember('leaf_categories_list', 3600, function () {
            return Category::where('is_page', true)->get(['id', 'name']);
        });

        foreach ($rules as $categoryKey => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    // Find category matching keyword
                    $matched = $categories->first(function ($cat) use ($categoryKey) {
                        return str_contains(mb_strtolower($cat->name), $categoryKey);
                    });
                    if ($matched) {
                        return $matched->id;
                    }
                }
            }
        }

        // Default to "Boshqa" (Others)
        $boshqa = $categories->first(function ($c) {
            return str_contains(mb_strtolower($c->name), 'boshqa');
        });

        return $boshqa?->id ?? 55;
    }

    /**
     * Guess district by keywords in text, or fallback to channel name.
     */
    public function guessDistrictId(string $text, ?string $channel = null): ?int
    {
        $lower = mb_strtolower(self::cyrillicToLatin($text));
        $districts = Cache::remember('districts_list', 3600, function () {
            return District::all(['id', 'name']);
        });

        // 1. Text explicit mentions with aliases (after converting to Latin)
        $districtKeywords = [
            1 => ['uchquduq', 'uchkuduk', '11-mavze', '12-mavze', '13-mavze', 'avtovokzal uchquduq'],
            2 => ['zarafshon', 'zarafshan', '1-kichik', '2-kichik', '3-kichik', '4-kichik'],
            3 => ['navoiy', 'navoi', 'janubiy', 'shevchenko', 'g\'alaba shoh', 'galaba shoh'],
            4 => ['karmana', 'varq', 'malik', 'ko\'hna karmana', 'kohna karmana', 'talqoq'],
            5 => ['qiziltepa', 'kiziltepa', 'vangozi', 'vang\'ozi'],
        ];

        foreach ($districtKeywords as $dId => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    return $dId;
                }
            }
        }

        // Direct DB district names check
        foreach ($districts as $district) {
            $dName = mb_strtolower(self::cyrillicToLatin($district->name));
            if (str_contains($lower, $dName)) {
                return $district->id;
            }
        }

        // 2. Second priority: Fallback to source channel name
        if (!empty($channel)) {
            $chLower = mb_strtolower(self::cyrillicToLatin($channel));
            foreach ($districtKeywords as $dId => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($chLower, $kw)) {
                        return $dId;
                    }
                }
            }

            foreach ($districts as $district) {
                $dName = mb_strtolower(self::cyrillicToLatin($district->name));
                if (str_contains($chLower, $dName)) {
                    return $district->id;
                }
            }
        }

        return null;
    }

    /**
     * Normalize parsed data and ensure valid category / price_type / district IDs.
     */
    private function normalizeParsedData(array $data, string $rawText, ?string $channel = null): array
    {
        $priceTypes = Cache::remember('price_types_list', 3600, function () {
            return PriceType::all(['id', 'name']);
        });

        $currency = strtoupper($data['currency'] ?? 'UZS');
        $priceTypeId = $priceTypes->firstWhere('name', $currency)?->id ?? 1;

        $categories = Cache::remember('leaf_categories_list', 3600, function () {
            return Category::where('is_page', true)->get(['id', 'name']);
        });

        $categoryId = (int) ($data['category_id'] ?? 0);
        if (!$categories->contains('id', $categoryId)) {
            $categoryId = $this->guessCategoryId($rawText);
        }

        $districts = Cache::remember('districts_list', 3600, function () {
            return District::all(['id', 'name']);
        });

        // Always prioritize the accurately resolved district from text or channel
        $resolvedDistrictId = $this->guessDistrictId($rawText, $channel);
        $districtId = $resolvedDistrictId ?: (!empty($data['district_id']) ? (int) $data['district_id'] : null);
        if (!$districtId || !$districts->contains('id', $districtId)) {
            $districtId = $resolvedDistrictId;
        }

        $title = !empty($data['title']) ? trim($data['title']) : 'E\'lon';
        $desc = !empty($data['description']) ? trim($data['description']) : $rawText;

        $title = self::cleanTitle($title);
        $desc = self::cleanDescription($desc);

        return [
            'is_valid' => true,
            'title' => mb_substr($title, 0, 80),
            'description' => $desc,
            'price' => !empty($data['price']) && is_numeric($data['price']) ? (int) $data['price'] : null,
            'price_type_id' => $priceTypeId,
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'category_id' => $categoryId,
            'district_id' => $districtId,
        ];
    }

    /**
     * Transliterate Uzbek Cyrillic text to pure Latin.
     */
    public static function cyrillicToLatin(string $text): string
    {
        $map = [
            'Ғ' => "G'", 'ғ' => "g'",
            'Ў' => "O'", 'ў' => "o'",
            'Қ' => 'Q',  'қ' => 'q',
            'Ҳ' => 'H',  'ҳ' => 'h',
            'Ч' => 'Ch', 'ч' => 'ch',
            'Ш' => 'Sh', 'ш' => 'sh',
            'Щ' => 'Sh', 'щ' => 'sh',
            'Ё' => 'Yo', 'ё' => 'yo',
            'Ю' => 'Yu', 'ю' => 'yu',
            'Я' => 'Ya', 'я' => 'ya',
            'Ц' => 'Ts', 'ц' => 'ts',
            'Ж' => 'J',  'ж' => 'j',
            'А' => 'A',  'а' => 'a',
            'Б' => 'B',  'б' => 'b',
            'В' => 'V',  'в' => 'v',
            'Г' => 'G',  'г' => 'g',
            'Д' => 'D',  'д' => 'd',
            'Е' => 'E',  'е' => 'e',
            'З' => 'Z',  'з' => 'z',
            'И' => 'I',  'и' => 'i',
            'Й' => 'Y',  'й' => 'y',
            'К' => 'K',  'к' => 'k',
            'Л' => 'L',  'л' => 'l',
            'М' => 'M',  'м' => 'm',
            'Н' => 'N',  'н' => 'n',
            'О' => 'O',  'о' => 'o',
            'П' => 'P',  'п' => 'p',
            'Р' => 'R',  'р' => 'r',
            'С' => 'S',  'с' => 's',
            'Т' => 'T',  'т' => 't',
            'У' => 'U',  'у' => 'u',
            'Ф' => 'F',  'ф' => 'f',
            'Х' => 'X',  'х' => 'x',
            'Ъ' => "'",  'ъ' => "'",
            'Ь' => '',   'ь' => '',
            'Э' => 'E',  'э' => 'e',
        ];

        return strtr($text, $map);
    }

    /**
     * Clean and format title: pure Latin, no spam shoutouts.
     */
    public static function cleanTitle(string $title): string
    {
        $clean = self::cyrillicToLatin($title);

        // Strip spam prefixes: "AKSIYA!", "DIQQAT!", etc.
        $clean = preg_replace('/^(?:🔥|❗️|⚡️|✨|👉|✅|💥|‼️)?\s*(?:AKSIYA(?:\s+BOSHLANDI)?|DIQQAT|SHOSHILING|ARZON|TEZDA|SUPER\s+TAKLIF)\s*[!.:,-]*\s*/ui', '', $clean);

        // Remove hashtags and external telegram channel tags
        $clean = preg_replace('/#\w+/u', '', $clean);
        $clean = preg_replace('/@\w+/u', '', $clean);

        // Replace common Russian / mixed terms
        $replaces = [
            '/\buglovoy\b/ui' => 'burchak',
            '/\bnovostroyka\b/ui' => 'yangi bino',
            '/\betaj\b/ui' => 'qavat',
            '/\bkvadrat\b/ui' => 'kv.m',
        ];
        $clean = preg_replace(array_keys($replaces), array_values($replaces), $clean);

        // Clean extra spaces
        $clean = trim(preg_replace('/\s+/u', ' ', $clean));

        return mb_substr($clean, 0, 70);
    }

    /**
     * Clean description: pure Latin, remove channel ads, clean layout.
     */
    public static function cleanDescription(string $desc): string
    {
        $text = self::cyrillicToLatin($desc);

        // Remove spam lines and channel promotions
        $lines = preg_split('/[\r\n]+/u', $text);
        $filteredLines = [];

        $spamPatterns = [
            '/t\.me\//i',
            '/instagram(?:\.com)?/i',
            '/obuna\s+bo[\'’`]?ling/ui',
            '/a[\'’`]?zo\s+bo[\'’`]?ling/ui',
            '/kanalimizga/ui',
            '/do[\'’`]?stlarga\s+ulashing/ui',
            '/admin\s+(?:ga|bilan)/ui',
            '/reklama\s+berish/ui',
            '/rasmiy\s+kanal/ui',
            '/bizning\s+kanal/ui',
            '/do[\'’`]?konlarimiz/ui',
            '/lokatsiyalar/ui',
            '/kanalga\s+ulaning/ui',
        ];

        foreach ($lines as $line) {
            $lineTrimmed = trim($line);
            if (empty($lineTrimmed)) {
                $filteredLines[] = '';
                continue;
            }

            $isSpam = false;
            foreach ($spamPatterns as $pat) {
                if (preg_match($pat, $lineTrimmed)) {
                    $isSpam = true;
                    break;
                }
            }

            if (!$isSpam) {
                $filteredLines[] = $lineTrimmed;
            }
        }

        $text = implode("\n", $filteredLines);

        // Replace common Russian / jargon terms with clean Uzbek
        $replacements = [
            '/\borientir\s*:/ui' => "Mo'ljal:",
            '/\blokat[sc]iya\s*:/ui' => "Manzil:",
            '/\buglovoy\b/ui' => "burchakdagi",
            '/\bremont\b/ui' => "ta'mir",
            '/\bso[\'’`]?tix\b/ui' => "sotix",
        ];
        $text = preg_replace(array_keys($replacements), array_values($replacements), $text);

        // Remove all emojis / icons, preserve bullet point (•) and clean punctuation
        $text = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE00}-\x{FE0F}]/u', '', $text);

        // Collapse multiple blank lines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    private function getCategoriesContext(): string
    {
        $categories = Cache::remember('leaf_categories_with_parents', 3600, function () {
            return Category::where('is_page', true)->with('parent.parent')->get(['id', 'name', 'parent_id']);
        });

        return $categories->map(function ($c) {
            $path = [];
            if ($c->parent?->parent) {
                $path[] = $c->parent->parent->name;
            }
            if ($c->parent) {
                $path[] = $c->parent->name;
            }
            $path[] = $c->name;
            $fullName = implode(' > ', $path);

            return "ID {$c->id}: {$fullName}";
        })->implode(', ');
    }

    private function getDistrictsContext(): string
    {
        $districts = Cache::remember('districts_list', 3600, function () {
            return District::all(['id', 'name']);
        });

        return $districts->map(fn($d) => "ID {$d->id}: {$d->name}")->implode(', ');
    }
}

<?php

namespace App\Services\Bot;

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\District;
use App\Models\PriceType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdAiParserService
{
    /**
     * Parse raw ad text into structured product data.
     */
    public function parse(string $rawText, BotSetting $setting, ?string $channel = null): ?array
    {
        $rawText = trim($rawText);
        if (mb_strlen($rawText) < 15) {
            return null;
        }

        // Basic filter: must have a phone number or telegram @username
        $hasContact = preg_match('/(?:\+?998[\s-]?)?(?:\(?\d{2}\)?[\s-]?)?\d{3}[\s-]?\d{2}[\s-]?\d{2}/', $rawText)
            || preg_match('/@[a-zA-Z0-9_]{4,}/', $rawText)
            || preg_match('/(?:tel|aloqa|nomer|telefon|telegram|lichka|murojaat)\s*[:\-]?\s*[\d+@]/ui', $rawText);

        if (!$hasContact) {
            return null;
        }

        // Filter out obvious spam / advertisements
        if ($this->isObviousSpam($rawText)) {
            return null;
        }

        // Agar maxsus qoidali regex parser tanlangan bo'lsa:
        if ($setting->ai_provider === 'regex') {
            return $this->normalizeParsedData($this->fallbackParse($rawText, $channel), $rawText, $channel);
        }

        // AI orqali tahlil qilish (Gemini yoki OpenAI)
        $provider = $setting->ai_provider ?: 'gemini';
        $apiKey = $setting->ai_api_key ?: ($provider === 'openai' ? env('OPENAI_API_KEY') : env('GEMINI_API_KEY'));

        if (empty($apiKey)) {
            $this->notifyTelegramFailure(strtoupper($provider), "API kalit topilmadi (.env yoki bot sozlamalarida {$provider} kaliti kiritilmagan).");
            // AI ishlamasa hech narsa yozilmasin:
            return null;
        }

        $aiResult = null;
        if ($provider === 'openai') {
            $aiResult = $this->callOpenAi($rawText, $apiKey);
        } else {
            $aiResult = $this->callGemini($rawText, $apiKey, $channel);
        }

        if ($aiResult && !empty($aiResult['is_valid'])) {
            return $this->normalizeParsedData($aiResult, $rawText, $channel);
        }

        // Agar AI ishlamasa yoki xato bo'lsa - e'lon yozilmaydi!
        return null;
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
     * Call Google Gemini API (gemini-flash-latest / gemini-3.8-flash).
     */
    private function callGemini(string $rawText, string $apiKey, ?string $channel = null): ?array
    {
        try {
            $categoriesContext = $this->getCategoriesContext();
            $districtsContext = $this->getDistrictsContext();
            $boshqaCategory = Category::where('name', 'like', '%Boshqa%')->first();
            $boshqaId = $boshqaCategory?->id ?? 55;
            $channelInfo = !empty($channel) ? "Manba kanal: @{$channel}\n" : '';

            $detectedDistrictId = $this->guessDistrictId($rawText, $channel);
            $districtHint = '';
            if ($detectedDistrictId) {
                $districts = Cache::remember('districts_list', 3600, fn() => District::all(['id', 'name']));
                $dObj = $districts->firstWhere('id', $detectedDistrictId);
                if ($dObj) {
                    $districtHint = "- ANIQ HUDUD: Ushbu e'lon matni yoki @{$channel} kanaliga ko'ra shahar/tuman QAT'IY {$dObj->name} (ID: {$detectedDistrictId}) bo'lishi SHART! Boshqa tumanga adashtirma!\n";
                }
            }

            $prompt = <<<PROMPT
Sen O'zbekiston viloyatlari bo'yicha e'lonlar doskasi uchun professional copywriter (muharrir) AI assistentsan.
Vazifang: Telegram kanallardan olingan xom, betartib xabarlarni sayt uchun TUSHUNARLI, CHIROYLI, JOZIBADOR va 100% USER-FRIENDLY e'longa aylantirish.

{$channelInfo}Mavjud Kategoriyalar:
{$categoriesContext}

Mavjud Tumanlar/Shaharlar:
{$districtsContext}

QAT'IY TALABLAR (BU TALABLARNI BUZISH TAQIQLANADI):

1. DO'KONLAR, MAGAZINLAR VA AKSIYALAR TAQIQLANGAN (ENG MUHIM QOIDA):
   - Do'kon, magazin, market, shourum, optom tovar, aksiya, skidka, chegirma yoki biznes reklamalari bo'lsa, DARHOL {"is_valid": false} deb qaytar!
   - FAQAT haqiqiy jismoniy shaxs (oddiy inson) o'zining shaxsiy mulkini (uy, mashina, telefon, buyum) sotayotgan yoki shaxsiy ish/xizmat taklif qilayotgan bo'lsa qabul qil!

2. TIL VA ALIFBO:
   - Barcha matnlar (title ham, description ham) FAQAT VA FAQAT O'ZBEK LOTIN ALIFBOSIDA bo'lishi SHART!
   - Asl e'londagi kirillcha so'zlarni ("сотилади", "сотих", "кўчаси", "уй", "ишчи керак", "хонадон", "келишилади") TO'LIQ O'ZBEK LOTIN ALIFBOSIGA O'GIRING ("sotiladi", "sotix", "ko'chasi", "uy", "ishchi kerak", "xonadon", "kelishiladi").
   - Ruscha so'zlarni chiroyli o'zbekchaga o'gir ("угловой" -> "burchakdagi", "ориентир" -> "mo'ljal", "ремонт" -> "ta'mir", "новостройка" -> "yangi bino", "этаж" -> "qavat", "холодильник" -> "muzlatgich").

3. "title" (Sarlavha):
   - Qisqa, aniq va jozibador bo'lsin (maksimum 45-50 belgi).
   - Masalan: "3 xonali burchak kottedj sotiladi", "2 xonali kvartira sotiladi", "Spark 2019 mexanika oq rang".
   - "AKSIYA!", "DIQQAT!", "SHOSHILING!", kanal nomlari yoki telefon raqamlarni sarlavhaga QO'YMA.

4. "description" (Tavsif - User-Friendly, faqat nuqtachalar bilan):
   - HECH QANDAY EMOJI YOKI IKONKA (📍, 💵, 📞, 🚗, 📱, ✨ va boshqalar) ISHLATILMASIN! Faqat oddiy nuqtachalar (•) ishlatilsin.
   - Chiroyli abzaslar va yangi qatorlar bilan formatla:
     * 1-bo'lim: Mahsulot haqida 1-2 gaplik aniq ma'lumot;
     * 2-bo'lim (bo'sh qator tashlab): Asosiy parametrlar (• Holati: a'lo\n• Rangi: oq);
     * 3-bo'lim (bo'sh qator tashlab): • Manzil: ... (agar matnda bo'lsa);
     * 4-bo'lim (bo'sh qator tashlab): • Narxi: ... (agar matnda bo'lsa);
     * 5-bo'lim (bo'sh qator tashlab): • Bog'lanish uchun: FAQAT telefon raqam(lar)i! Hech qanday @username yozilmasin!
   - Spam va begona kanallar reklamalarini (@..., t.me/...) butunlay tozalab tashla!

5. "district_id" (Shahar/Tuman):
   {$districtHint}- Agar matnda shahar nomi yozilgan bo'lsa o'shani tanla, yozilmagan bo'lsa manba kanal qaysi tumanniki bo'lsa o'sha tuman ID sini tanla.

6. "category_id":
   - Bitta aniq tovar bo'lsa mos kategoriya ID raqami. Aralash yoki noaniq bo'lsa "Boshqa" (ID {$boshqaId}) ni tanla.

JSON formati:
{
  "is_valid": true,
  "title": "Sodda va aniq sarlavha (toza lotin tilida)",
  "description": "Tozalangan, tartibli va user-friendly tavsif matni (toza lotin tilida)",
  "price": 12000000, // raqam yoki null
  "currency": "UZS", // "UZS" yoki "USD" yoki null
  "phone": "998901234567", // 12 xonali formatda yoki null
  "category_id": 12,
  "district_id": 1
}

Agar bu xabar shaxsiy e'lon bo'lmasa, magazin/do'kon, aksiya yoki spam bo'lsa, faqat {"is_valid": false} deb qaytar.

E'lon matni:
{$rawText}
PROMPT;

            $model = 'gemini-flash-latest';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(15)
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.2,
                    ]
                ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                if ($content) {
                    $cleanJson = trim($content);
                    if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $cleanJson, $matches)) {
                        $cleanJson = trim($matches[1]);
                    }
                    $decoded = json_decode($cleanJson, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            } else {
                $errorData = $response->json('error');
                $errorMsg = is_array($errorData) ? ($errorData['message'] ?? $response->body()) : $response->body();
                Log::warning("Gemini API ({$model}) xatoligi: " . $errorMsg);
                $this->notifyTelegramFailure("Gemini ({$model})", $errorMsg);
            }
        } catch (\Throwable $e) {
            Log::error("Gemini API chaqirishda xatolik: " . $e->getMessage());
            $this->notifyTelegramFailure("Gemini", $e->getMessage());
        }

        return null;
    }

    /**
     * Call OpenAI API (gpt-4o-mini).
     */
    private function callOpenAi(string $rawText, string $apiKey): ?array
    {
        try {
            $categoriesContext = $this->getCategoriesContext();
            $districtsContext = $this->getDistrictsContext();

            $systemPrompt = "Sen e'lonlarni tahlil qiluvchi professional muharrir assistentsan. Javobing FAQAT toza JSON bo'lishi shart. Barcha matnlarni (title va description) FAQAT toza O'zbek lotin alifbosida, user-friendly tartibli qilib yoz, hech qachon kirill harflarini aralashtirma.\nKategoriyalar: {$categoriesContext}\nTumanlar: {$districtsContext}";

            $response = Http::timeout(15)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Tahlil qil:\n" . $rawText],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.1,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if ($content) {
                    $cleanJson = trim($content);
                    if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $cleanJson, $matches)) {
                        $cleanJson = trim($matches[1]);
                    }
                    $decoded = json_decode($cleanJson, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            } else {
                $errorMsg = $response->json('error.message') ?: $response->body();
                Log::warning("OpenAI API xatolik: " . $errorMsg);
                $this->notifyTelegramFailure("OpenAI (gpt-4o-mini)", $errorMsg);
            }
        } catch (\Throwable $e) {
            Log::error("OpenAI API xatolik: " . $e->getMessage());
            $this->notifyTelegramFailure("OpenAI", $e->getMessage());
        }

        return null;
    }

    /**
     * Send an alert message to Telegram when AI service fails.
     */
    private function notifyTelegramFailure(string $provider, string $errorMessage): void
    {
        // Don't spam Telegram: send only once every 15 minutes per unique error
        $cacheKey = 'ai_error_alert_' . md5($provider . $errorMessage);
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, now()->addMinutes(15));

        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_ADMIN_CHAT_ID') ?: env('TELEGRAM_CHANNEL');

        if (empty($botToken) || empty($chatId)) {
            Log::warning("Telegramga ogohlantirish yuborilmadi: TELEGRAM_BOT_TOKEN yoki TELEGRAM_ADMIN_CHAT_ID bo'sh.");
            return;
        }

        try {
            $shortError = Str::limit(strip_tags($errorMessage), 350);
            $timeNow = now()->format('Y-m-d H:i:s');

            $message = "⚠️ <b>AI E'lon Tahlilida Xatolik!</b>\n\n"
                . "🤖 <b>Provayder:</b> " . htmlspecialchars($provider, ENT_QUOTES, 'UTF-8') . "\n"
                . "❌ <b>Xatolik sababi:</b>\n<code>" . htmlspecialchars($shortError, ENT_QUOTES, 'UTF-8') . "</code>\n\n"
                . "⏰ <b>Vaqt:</b> {$timeNow}\n\n"
                . "⛔ <b>Eslatma:</b> AI ishlamay qolgani sababli bu e'lonlar platformaga kiritilmadi (xunuk/buzilgan matnlar chiqmasligi uchun).";

            Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Throwable $e) {
            Log::error("Telegramga AI ogohlantirishini yuborishda xatolik: " . $e->getMessage());
        }
    }

    /**
     * Fallback rule-based parsing when AI is not configured or fails.
     */
    public function fallbackParse(string $rawText, ?string $channel = null): array
    {
        // 1. Phone extraction
        $phone = null;
        if (preg_match('/(?:\+?998[\s-]?)?\(?(\d{2})\)?[\s-]?(\d{3})[\s-]?(\d{2})[\s-]?(\d{2})/', $rawText, $phoneMatch)) {
            $phone = '998' . $phoneMatch[1] . $phoneMatch[2] . $phoneMatch[3] . $phoneMatch[4];
        } elseif (preg_match('/@([a-zA-Z0-9_]{4,})/', $rawText, $userMatch)) {
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

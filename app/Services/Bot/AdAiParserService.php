<?php

namespace App\Services\Bot;

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\District;
use App\Models\PriceType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        // Faqat AI orqali yozilsin - agar API key bo'lmasa yoki AI xato bersa, e'lon yozilmaydi
        $aiResult = null;
        $apiKey = $setting->ai_api_key ?: env('GEMINI_API_KEY') ?: env('OPENAI_API_KEY');

        if (empty($apiKey)) {
            Log::warning("AI parser: API key mavjud emas (BotSetting yoki .env). E'lon o'tkazib yuborildi.");
            return null;
        }

        if ($setting->ai_provider === 'openai') {
            $aiResult = $this->callOpenAi($rawText, $apiKey, $channel);
        } else {
            $aiResult = $this->callGemini($rawText, $apiKey, $channel);
        }

        if ($aiResult && !empty($aiResult['is_valid'])) {
            return $this->normalizeParsedData($aiResult, $rawText, $channel);
        }

        // Agar AI tahlil qila olmasa yoki e'lon yaroqsiz bo'lsa, hech narsa yozilmaydi
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

            $prompt = <<<PROMPT
Sen O'zbekiston viloyatlari bo'yicha e'lonlar doskasi uchun professional copywriter (muharrir) AI assistentsan.
Vazifang: Telegram kanallardan olingan xom, betartib, har xil alifboda yozilgan xabarlarni sayt uchun TUSHUNARLI, CHIROYLI, JOZIBADOR va 100% USER-FRIENDLY e'longa aylantirish.

{$channelInfo}Mavjud Kategoriyalar:
{$categoriesContext}

Mavjud Tumanlar/Shaharlar:
{$districtsContext}

QAT'IY TALABLAR (BU TALABLARNI BUZISH TAQIQLANADI):

1. TIL VA ALIFBO (ENG MUHIMI):
   - Barcha matnlar (title ham, description ham) FAQAT VA FAQAT O'ZBEK LOTIN ALIFBOSIDA bo'lishi SHART!
   - Asl e'londagi kirillcha so'zlarni (masalan: "сотилади", "сотих", "кўчаси", "уй", "ишчи керак", "хонадон", "келишилади") TO'LIQ O'ZBEK LOTIN ALIFBOSIGA O'GIRING ("sotiladi", "sotix", "ko'chasi", "uy", "ishchi kerak", "xonadon", "kelishiladi").
   - HECH QACHON yarmi lotin, yarmi kirill qilib aralashtirma (masalan: "4 soтих" QAT'IYAN XATO! "4 sotix" deb yoz; "угловой коттедж" XATO! "burchak kottedj" deb yoz)!
   - Ruscha so'zlarni chiroyli o'zbekchaga o'gir (masalan: "угловой" -> "burchakdagi", "ориентир" -> "mo'ljal", "ремонт" -> "ta'mir", "новостройка" -> "yangi bino", "этаж" -> "qavat", "холодильник" -> "muzlatgich").

2. "title" (Sarlavha):
   - Qisqa, aniq va jozibador bo'lsin (maksimum 45-50 belgi).
   - E'lonning asosiy mohiyatini darhol anglatuvchi sarlavha tuz:
     * Masalan: "3 xonali burchak kottedj sotiladi", "2 xonali kvartira sotiladi", "Spark 2019 mexanika oq rang", "Oziq-ovqat do'koniga sotuvchi kerak", "Yer uchastkasi sotiladi, 4 sotix".
   - "AKSIYA!", "DIQQAT!", "SHOSHILING!", kanal nomlari, telefon raqamlari yoki ma'nosiz belgilarni sarlavhaga QO'YMA.

3. "description" (Tavsif - User-Friendly):
   - Foydalanuvchi ko'zi bilan qaraganda o'qishga juda qulay, chiroyli va tartibli tuz:
     * 1-2 gapda narsa/xizmat haqida asosiy ma'lumot;
     * Asosiy qulayliklar va parametrlar (agar bor bo'lsa punktlar bilan):
       • Maydoni: ...
       • Xonalar soni: ...
       • Holati: ...
       • Jihozlari: ...
     * 📍 Manzil va mo'ljal (agar matnda bo'lsa);
     * 💵 Narxi va to'lov shartlari (agar matnda bo'lsa);
     * 📞 Bog'lanish: telefon raqami va telegram kontakt;
   - SPAM VA BEGONA REKLAMALARNI MUTLAQO O'CHIR:
     Kanal nomlari (@..., t.me/...), "Obuna bo'ling", "Kanalimizga ulaning", "Admin ga murojaat", "Do'stlarga ulashing", instagram, boshqa kanallar reklamasini BUTUNLAY tozalab tashla!

4. "category_id":
   - Agar bir nechta har xil tovarlar (masalan: bir e'londa telefon, televizor, changyutgich aralash) ko'rsatilgan bo'lsa yoki bitta aniq toifaga to'g'ri kelmasa, kategoriya sifatida majburiy tartibda "Boshqa" (ID {$boshqaId}) ni tanla!
   - Agar bitta aniq mahsulot bo'lsa, ro'yxatdagi eng mos kategoriya ID sini tanla.

5. "district_id" (Shahar/Tuman):
   - Agar e'lon matnida aniq shahar yoki tuman (masalan: Uchquduq, Navoiy, Zarafshon, Karmana, Qiziltepa) nomi yozilgan bo'lsa, O'SHA tumanni tanla.
   - Agar e'lon matnida aniq tuman yozilmagan bo'lsa, manba kanal qaysi shaharga tegishli bo'lsa o'shani tanla (masalan: @Uchquduq_24, @uchquduqxabar bo'lsa Uchquduq (ID 1); @navoiy... bo'lsa Navoiy (ID 3); @karmana... bo'lsa Karmana (ID 4)).

JSON formati:
{
  "is_valid": true,
  "title": "Sodda va aniq sarlavha (toza lotin tilida)",
  "description": "Tozalangan, tartibli va user-friendly tavsif matni (toza lotin tilida)",
  "price": 12000000, // raqam yoki null
  "currency": "UZS", // "UZS" yoki "USD" yoki null
  "phone": "998901234567", // 12 xonali formatda yoki null
  "category_id": 12, // eng mos kategoriya ID raqami (aralash/noaniq bo'lsa ID {$boshqaId})
  "district_id": 1 // shahar/tuman ID raqami
}

Agar bu xabar tijoriy e'lon (tovar sotish, ijaraga berish, ish yoki xizmat) bo'lmasa yoki spam bo'lsa, {"is_valid": false} deb qaytar.

E'lon matni:
{$rawText}
PROMPT;

            // Use current Google Gemini models (Gemini 3.8 Flash recommended by Google API)
            $models = ['models/gemini-3.8-flash', 'models/gemini-3.8-flash-lite', 'models/gemini-3.5-flash', 'models/gemini-2.5-flash'];
            foreach ($models as $model) {
                $url = "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent?key={$apiKey}";

                $response = Http::timeout(25)
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
                            'temperature' => 0.1,
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
                    Log::warning("Gemini API ({$model}) xatoligi: " . $response->body());
                }
            }
        } catch (\Throwable $e) {
            Log::error("Gemini API chaqirishda xatolik: " . $e->getMessage());
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

            $response = Http::timeout(25)
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
            }
        } catch (\Throwable $e) {
            Log::error("OpenAI API xatolik: " . $e->getMessage());
        }

        return null;
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
    private function guessDistrictId(string $text, ?string $channel = null): ?int
    {
        $lower = mb_strtolower($text);
        $districts = Cache::remember('districts_list', 3600, function () {
            return District::all(['id', 'name']);
        });

        // 1. First priority: Check if text explicitly mentions any district/city
        foreach ($districts as $district) {
            $dName = mb_strtolower($district->name);
            if (str_contains($lower, $dName)) {
                return $district->id;
            }
        }

        // Common aliases in text (e.g. Navoi -> Navoiy, Uchkuduk -> Uchquduq, Zarafshan -> Zarafshon)
        $aliases = [
            'navoi' => 3,
            'uchkuduk' => 1,
            'zarafshan' => 2,
        ];
        foreach ($aliases as $alias => $dId) {
            if (str_contains($lower, $alias)) {
                return $dId;
            }
        }

        // 2. Second priority: Fallback to channel name
        if (!empty($channel)) {
            $chLower = mb_strtolower($channel);
            foreach ($districts as $district) {
                $dName = mb_strtolower($district->name);
                if (str_contains($chLower, $dName)) {
                    return $district->id;
                }
            }
            foreach ($aliases as $alias => $dId) {
                if (str_contains($chLower, $alias)) {
                    return $dId;
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

        $districtId = !empty($data['district_id']) ? (int) $data['district_id'] : null;
        if (!$districtId || !$districts->contains('id', $districtId)) {
            $districtId = $this->guessDistrictId($rawText, $channel);
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

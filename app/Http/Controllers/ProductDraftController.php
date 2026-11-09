<?php

namespace App\Http\Controllers;

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Bot\AdAiParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * "Matn bilan e'lon": foydalanuvchi guruhga yozgandek qisqa matn yozadi, AI undan e'lon formasini to'ldiradi.
 * E'lonni bu yerda saqlamaymiz — foydalanuvchi to'ldirilgan formani ko'rib, odatdagidek joylaydi.
 */
class ProductDraftController extends Controller
{
    /** Bir foydalanuvchiga kuniga AI so'rovlari (adminga cheklov yo'q) */
    public const DAILY_LIMIT = 5;

    /** Sarlavhalar shuncha foiz o'xshash bo'lsa — "sizda shu e'lon bor" */
    private const DUPLICATE_SIMILARITY = 80;

    public function __invoke(Request $request, AdAiParserService $parser): JsonResponse
    {
        $user = $request->user();
        abort_if($user->isBlocked(), 403, __('messages.blocked'));

        $validated = $request->validate([
            'text' => ['required', 'string', 'min:5', 'max:1500'],
        ]);

        $isAdmin = $user->role === 'admin';
        // Kalitda sana bor: limit yarim tunda yangilanadi ("bugun 5 ta")
        $key = 'ai-draft:' . $user->id . ':' . now()->toDateString();
        if (! $isAdmin) {
            if (RateLimiter::tooManyAttempts($key, self::DAILY_LIMIT)) {
                return response()->json([
                    'message' => __('messages.ai_draft_limit', ['max' => self::DAILY_LIMIT]),
                    'remaining' => 0,
                ], 429);
            }
            RateLimiter::hit($key, (int) now()->diffInSeconds(now()->endOfDay()) + 60);
        }

        $draft = $parser->parseUserText($validated['text'], BotSetting::current());
        // Formadagi telefon maydoni faqat raqam qabul qiladi; o'z username'ini foydalanuvchi formada tanlaydi
        if (str_starts_with((string) $draft['phone'], '@')) {
            $draft['phone'] = null;
        }
        $withPrice = Category::whereKey($draft['category_id'])->value('with_price') !== false;

        return response()->json([
            'draft' => [
                'category_id' => $draft['category_id'],
                'title' => $draft['title'],
                'description' => $draft['description'],
                'price' => $draft['price'],
                'price_type_id' => $draft['price_type_id'],
                'phone' => $draft['phone'],
                'district_id' => $draft['district_id'],
                'parameters' => (object) $draft['parameters'],
            ],
            // Matnda topilmagan, foydalanuvchi formada to'ldirishi kerak bo'lgan maydonlar
            'missing' => array_values(array_filter([
                $draft['district_id'] ? null : 'district',
                $draft['phone'] || $user->phone ? null : 'phone',
                $withPrice && ! $draft['price'] ? 'price' : null,
            ])),
            'duplicate' => $this->findDuplicate($user, $draft['category_id'], $draft['title']),
            'remaining' => $isAdmin ? null : RateLimiter::remaining($key, self::DAILY_LIMIT),
        ]);
    }

    /** Shu foydalanuvchining shu bo'limdagi o'xshash sarlavhali faol e'loni */
    private function findDuplicate(User $user, int $categoryId, string $title): ?array
    {
        $normalize = fn (string $text) => preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower(AdAiParserService::cyrillicToLatin($text)));
        $needle = trim($normalize($title));

        $match = Product::where('user_id', $user->id)
            ->where('category_id', $categoryId)
            ->active()
            ->whereNull('sold_at')
            ->latest('id')
            ->limit(20)
            ->get(['id', 'title', 'created_at'])
            ->first(function (Product $product) use ($normalize, $needle) {
                similar_text($needle, trim($normalize($product->title)), $percent);

                return $percent >= self::DUPLICATE_SIMILARITY;
            });

        return $match ? ['id' => $match->id, 'title' => $match->title] : null;
    }
}

<?php

namespace App\Services;

use App\Models\Category;
use App\Models\District;
use App\Models\Product;
use App\Support\UzbekText;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductSearchService
{
    // Kartalar ro'yxatida bundan ko'pini hech kim varaqlamaydi; aniqroq so'z yozgan ma'qul
    private const RESULT_LIMIT = 100;

    // Aniq natija shundan kam bo'lsa — pastda "«ish» so'ziga mos" e'lonlar ham ko'rsatiladi
    private const RELATED_WHEN_FEWER = 5;
    private const RELATED_LIMIT = 50;

    // Deyarli har e'londa uchraydigan so'zlar: faqat shular bo'yicha "o'xshash" ko'rsatish foydasiz
    private const WEAK_TERMS = [
        'kerak', 'sotiladi', 'sotaman', 'sotamiz', 'olaman', 'olamiz', 'bor', 'yangi', 'arzon', 'uchun',
        'va', 'bilan', 'ham', 'tez', 'srochno', 'shoshilinch', 'ijara', 'ijaraga', 'xizmat',
    ];

    /**
     * Qidiruv natijasi:
     *   products — so'rovdagi barcha so'zlarga mos e'lonlar;
     *   related  — aniq natija kam bo'lsa: so'zlarning bir qismiga mos e'lonlar va qaysi so'zlarga mosligi
     *              ("hamshira ish" → hamshira topilmadi, «ish» so'ziga mos e'lonlar).
     *
     * @return array{products: Collection, related: array{terms: string[], products: Collection}|null}
     */
    public function search(Request $request): array
    {
        $result = $this->score($request);
        $products = $this->load($request, $result['strict']);

        $related = null;
        if (count($result['strict']) < self::RELATED_WHEN_FEWER && $result['related']) {
            $related = [
                'terms' => $result['related']['terms'],
                'products' => $this->load($request, $result['related']['ids']),
            ];
        }

        return ['products' => $products, 'related' => $related];
    }

    /**
     * So'rovdagi barcha so'zlarga mos faol e'lonlar id'lari (moslik bo'yicha tartiblangan).
     * Qidiruv ham, kuzatuvlar ham, filtr soni ham shu mantiqdan foydalanadi:
     * $scope bilan nomzodlar toraytiriladi (masalan, faqat oxirgi tekshiruvdan keyingi yangi e'lonlar).
     *
     * @return int[]
     */
    public function matchingIds(Request $request, ?\Closure $scope = null): array
    {
        return $this->score($request, $scope)['strict'];
    }

    /** To'liq ma'lumot (rasmlar, parametrlar...) faqat ko'rsatiladiganlar uchun */
    private function load(Request $request, array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $query = Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])->whereIn('id', $ids);

        // Foydalanuvchi saralashni tanlagan bo'lsa (arzon, qimmat...) — o'sha tartib; aks holda moslik bo'yicha
        $sort = $request->input('sort');
        if (in_array($sort, ProductFilters::SORTS, true)) {
            return ProductFilters::sort($query, $sort)->get();
        }

        $order = array_flip($ids);

        return $query->get()
            ->sortBy(fn (Product $product) => $order[$product->id])
            ->values();
    }

    /**
     * @return array{strict: int[], related: array{terms: string[], ids: int[]}|null}
     */
    private function score(Request $request, ?\Closure $scope = null): array
    {
        $rawQuery = (string) $request->input('search', '');
        $terms = preg_split('/\s+/u', UzbekText::normalize($rawQuery), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($terms)) {
            return ['strict' => [], 'related' => null];
        }

        // 1. Har so'z o'z kategoriyalarini topadi: "hamshira ish" da "Ish" bo'limi faqat «ish» so'ziga mos,
        //    «hamshira» ga emas (aks holda barcha ish e'lonlari "hamshira" deb chiqib ketardi)
        $allCategories = Category::all(['id', 'name', 'parent_id']);
        $termCategoryIds = [];
        foreach ($terms as $index => $term) {
            $pattern = mb_strlen($term) <= 4
                ? '/(^| )' . preg_quote($term, '/') . '( |$)/u'
                : '/(^| )' . preg_quote($term, '/') . '/u';

            $ids = [];
            foreach ($allCategories as $cat) {
                if (preg_match($pattern, UzbekText::normalize($cat->name))) {
                    $ids[] = $cat->id;
                    array_push($ids, ...$allCategories->where('parent_id', $cat->id)->pluck('id')->all());
                }
            }
            $termCategoryIds[$index] = array_flip($ids);
        }

        $districtNames = District::pluck('name', 'id')->map(fn ($name) => UzbekText::normalize($name));

        // 2. Nomzodlar: faqat matn ustunlari, rasm/parametr/kategoriya yuklamasdan (har so'rovda barcha faol e'lonlar)
        $candidates = Product::query()
            ->setEagerLoads([])
            ->select(['id', 'title', 'description', 'category_id', 'district_id'])
            ->active()
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->tap(fn ($q) => ProductFilters::apply($q, $request))
            ->when($scope, fn ($q) => $scope($q))
            ->latest('published_at')
            ->latest('id')
            ->toBase()
            ->get();

        // Parametr qiymatlari ("Mikroto'lqinli pech", "Cobalt", "Avtomat") ko'pincha sarlavhada yo'q — ular ham qidiriladi
        $paramsText = [];
        foreach ($candidates->pluck('id')->chunk(1000) as $chunk) {
            DB::table('product_parameter_values')
                ->whereIn('product_id', $chunk->all())
                ->get(['product_id', 'value'])
                ->each(function ($row) use (&$paramsText): void {
                    $paramsText[$row->product_id] = ($paramsText[$row->product_id] ?? '') . ' ' . $row->value;
                });
        }

        $patterns = array_map(fn (string $term) => $this->termPatterns($term), $terms);

        // 3. Har e'lon: umumiy ball va qaysi so'zlarga mosligi
        $strict = [];
        $partial = [];

        foreach ($candidates as $product) {
            $title = UzbekText::normalize($product->title);
            $desc = UzbekText::normalize($product->description);
            $params = UzbekText::normalize($paramsText[$product->id] ?? '');
            $districtName = $districtNames[$product->district_id] ?? '';

            $score = 0;
            $matched = [];

            foreach ($patterns as $index => [$exact, $prefix]) {
                $termScore = 0;

                if (preg_match($exact, $title)) {
                    $termScore += 50;
                } elseif (preg_match($prefix, $title)) {
                    $termScore += 35;
                }

                if (isset($termCategoryIds[$index][$product->category_id])) {
                    $termScore += 30;
                }

                if (preg_match($exact, $desc)) {
                    $termScore += 20;
                } elseif (preg_match($prefix, $desc)) {
                    $termScore += 15;
                }

                // Parametr qiymatlari (description darajasida)
                if (preg_match($prefix, $params)) {
                    $termScore += 15;
                }

                if (preg_match($prefix, $districtName)) {
                    $termScore += 10;
                }

                if ($termScore > 0) {
                    $matched[] = $index;
                    $score += $termScore;
                }
            }

            if (count($matched) === count($terms)) {
                $strict[] = ['id' => $product->id, 'score' => $score];
            } elseif ($matched) {
                $partial[implode(',', $matched)][] = ['id' => $product->id, 'score' => $score];
            }
        }

        return [
            'strict' => array_column(array_slice($this->rank($strict), 0, self::RESULT_LIMIT), 'id'),
            'related' => count($terms) > 1 ? $this->bestPartial($partial, $terms, $rawQuery) : null,
        ];
    }

    /**
     * Exact — butun so'z; prefix — so'z boshi (qisqa ildizlarda qo'shimchalar bilan cheklangan).
     *
     * @return array{0: string, 1: string}
     */
    private function termPatterns(string $term): array
    {
        $quoted = preg_quote($term, '/');
        $exact = '/(^| )' . $quoted . '( |$)/u';

        if ($term === 'ish') {
            // Match: ish, ishchi, ishchilar, ishga, ishda, ishdan, ishlar, ishsiz, ishxona, ish joyi
            // Reject: gisht, kelishildi, berish, sotish, qilish, yuvish, ishonch
            $prefix = '/(^| )ish(chi|ga|da|lar|siz|xona|joyi|orni| |$)/u';
        } elseif (mb_strlen($term) <= 3) {
            $prefix = '/(^| )' . $quoted . '( |$|da|ga|dan|lar|ning)/u';
        } else {
            $prefix = '/(^| )' . $quoted . '/u';
        }

        return [$exact, $prefix];
    }

    /** Ball bo'yicha kamayish, teng bo'lsa — yangisi oldin */
    private function rank(array $scored): array
    {
        usort($scored, fn ($a, $b) => [$b['score'], $b['id']] <=> [$a['score'], $a['id']]);

        return $scored;
    }

    /**
     * Qisman moslar ichidan eng ko'p so'zga mos guruh ("hamshira ish" → «ish»).
     * Faqat "kerak", "sotiladi" kabi umumiy so'zlarga mos guruhlar tashlab ketiladi.
     *
     * @param  array<string, array<int, array{id: int, score: int}>>  $partial
     * @return array{terms: string[], ids: int[]}|null
     */
    private function bestPartial(array $partial, array $terms, string $rawQuery): ?array
    {
        $best = null;
        $bestKey = null;

        foreach ($partial as $key => $items) {
            $indexes = array_map('intval', explode(',', (string) $key));
            $meaningful = array_filter($indexes, fn (int $i) => !in_array($terms[$i], self::WEAK_TERMS, true));
            if (!$meaningful) {
                continue;
            }

            // Ko'proq so'zga mos guruh yaxshi; teng bo'lsa — eng yuqori balli e'loni borrog'i
            $rank = [count($indexes), max(array_column($items, 'score'))];
            if ($best === null || $rank > $best) {
                $best = $rank;
                $bestKey = $key;
            }
        }

        if ($bestKey === null) {
            return null;
        }

        // Yorliqda foydalanuvchi yozgan shakl ("Ish"), so'zlar soni mos kelmasa — normallashgani
        $original = preg_split('/\s+/u', trim($rawQuery), -1, PREG_SPLIT_NO_EMPTY);
        $labels = array_map(
            fn (int $i) => count($original) === count($terms) ? $original[$i] : $terms[$i],
            array_map('intval', explode(',', (string) $bestKey)),
        );

        return [
            'terms' => $labels,
            'ids' => array_column(array_slice($this->rank($partial[$bestKey]), 0, self::RELATED_LIMIT), 'id'),
        ];
    }
}

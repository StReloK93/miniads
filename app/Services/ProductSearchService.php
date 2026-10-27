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

    /**
     * Search products with intelligent Uzbek-aware word-boundary and category matching.
     * Matn ham, so'rov ham UzbekText::normalize'dan o'tadi: kirill/lotin va tutuq belgilari farq qilmaydi.
     */
    public function search(Request $request): Collection
    {
        $ids = $this->matchingIds($request);
        if (empty($ids)) {
            return collect();
        }

        // To'liq ma'lumot (rasmlar, parametrlar...) faqat ko'rsatiladiganlar uchun
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
     * Mos faol e'lonlar id'lari (moslik bo'yicha tartiblangan). Qidiruv ham, kuzatuvlar ham shu mantiqdan foydalanadi:
     * $scope bilan nomzodlar toraytiriladi (masalan, faqat oxirgi tekshiruvdan keyingi yangi e'lonlar).
     *
     * @return int[]
     */
    public function matchingIds(Request $request, ?\Closure $scope = null): array
    {
        $terms = preg_split('/\s+/u', UzbekText::normalize((string) $request->input('search', '')), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($terms)) {
            return [];
        }

        // 1. Identify category matches
        $allCategories = Category::all(['id', 'name', 'parent_id']);
        $matchedCategoryIds = [];
        foreach ($allCategories as $cat) {
            $catName = UzbekText::normalize($cat->name);
            foreach ($terms as $term) {
                $pattern = mb_strlen($term) <= 4
                    ? '/(^| )' . preg_quote($term, '/') . '( |$)/u'
                    : '/(^| )' . preg_quote($term, '/') . '/u';

                if (preg_match($pattern, $catName)) {
                    $matchedCategoryIds[] = $cat->id;
                    $childrenIds = $allCategories->where('parent_id', $cat->id)->pluck('id')->all();
                    $matchedCategoryIds = array_merge($matchedCategoryIds, $childrenIds);
                    break;
                }
            }
        }
        $matchedCategoryIds = array_unique($matchedCategoryIds);

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

        // 3. Filter & score results
        $scored = [];

        foreach ($candidates as $product) {
            $title = UzbekText::normalize($product->title);
            $desc = UzbekText::normalize($product->description);
            $params = UzbekText::normalize($paramsText[$product->id] ?? '');
            $districtName = $districtNames[$product->district_id] ?? '';

            $score = 0;
            $matchedAllTerms = true;

            foreach ($terms as $term) {
                $termLen = mb_strlen($term);
                $quoted = preg_quote($term, '/');

                // Exact whole word pattern
                $exactWordPattern = '/(^| )' . $quoted . '( |$)/u';

                // Word prefix pattern (handling short roots like "ish", "uy" without matching suffixes like "-ish")
                if ($term === 'ish') {
                    // Match: ish, ishchi, ishchilar, ishga, ishda, ishdan, ishlar, ishsiz, ishxona, ish joyi
                    // Reject: gisht, kelishildi, berish, sotish, qilish, yuvish, ishonch
                    // Qo'shimcha yoki so'z oxiri ("ishchilarga" ham topiladi)
                    $wordPrefixPattern = '/(^| )ish(chi|ga|da|lar|siz|xona|joyi|orni| |$)/u';
                } elseif ($termLen <= 3) {
                    $wordPrefixPattern = '/(^| )' . $quoted . '( |$|da|ga|dan|lar|ning)/u';
                } else {
                    $wordPrefixPattern = '/(^| )' . $quoted . '/u';
                }

                $termMatched = false;

                // Title match (highest weight)
                if (preg_match($exactWordPattern, $title)) {
                    $score += 50;
                    $termMatched = true;
                } elseif (preg_match($wordPrefixPattern, $title)) {
                    $score += 35;
                    $termMatched = true;
                }

                // Category match
                if (in_array($product->category_id, $matchedCategoryIds)) {
                    $score += 30;
                    $termMatched = true;
                }

                // Description match
                if (preg_match($exactWordPattern, $desc)) {
                    $score += 20;
                    $termMatched = true;
                } elseif (preg_match($wordPrefixPattern, $desc)) {
                    $score += 15;
                    $termMatched = true;
                }

                // Parameter values match (description darajasida)
                if (preg_match($wordPrefixPattern, $params)) {
                    $score += 15;
                    $termMatched = true;
                }

                // District match
                if (preg_match($wordPrefixPattern, $districtName)) {
                    $score += 10;
                    $termMatched = true;
                }

                if (!$termMatched) {
                    $matchedAllTerms = false;
                    break;
                }
            }

            if ($matchedAllTerms && $score > 0) {
                $scored[] = ['id' => $product->id, 'score' => $score];
            }
        }

        // Sort by score DESC, then ID DESC
        usort($scored, function ($a, $b) {
            if ($b['score'] === $a['score']) {
                return $b['id'] <=> $a['id'];
            }
            return $b['score'] <=> $a['score'];
        });

        return array_column(array_slice($scored, 0, self::RESULT_LIMIT), 'id');
    }
}

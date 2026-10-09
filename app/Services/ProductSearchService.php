<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductSearchService
{
    /**
     * Search products with intelligent Uzbek-aware word-boundary and category matching.
     */
    public function search(Request $request): Collection
    {
        $search = trim((string) $request->input('search', ''));
        if ($search === '') {
            return collect();
        }

        $terms = preg_split('/\s+/u', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($terms)) {
            return collect();
        }

        // Uzbek word character class: Latin, Cyrillic, numbers, and Uzbek apostrophes
        $wordCharClass = 'a-z0-9а-яёўқғҳ\'ʻ’‘`';

        // 1. Identify category matches
        $allCategories = Category::all();
        $matchedCategoryIds = [];
        foreach ($allCategories as $cat) {
            $catName = mb_strtolower($cat->name);
            foreach ($terms as $term) {
                $termLen = mb_strlen($term);
                if ($termLen <= 4) {
                    $pattern = '/(^|[^' . $wordCharClass . '])' . preg_quote($term, '/') . '([^' . $wordCharClass . ']|$)/ui';
                } else {
                    $pattern = '/(^|[^' . $wordCharClass . '])' . preg_quote($term, '/') . '/ui';
                }

                if (preg_match($pattern, $catName)) {
                    $matchedCategoryIds[] = $cat->id;
                    $childrenIds = $allCategories->where('parent_id', $cat->id)->pluck('id')->all();
                    $matchedCategoryIds = array_merge($matchedCategoryIds, $childrenIds);
                    break;
                }
            }
        }
        $matchedCategoryIds = array_unique($matchedCategoryIds);

        // 2. Query candidates from database
        $products = Product::withExists([
            'favorites as is_favorite' => fn($q) => $q->where('user_id', auth()->id())
        ])
            ->active()
            ->when($request->city_id, fn($q) => $q->where('district_id', $request->city_id))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->price_from, fn($q) => $q->where('price', '>=', $request->price_from))
            ->when($request->price_to, fn($q) => $q->where('price', '<=', $request->price_to))
            ->latest('published_at')
            ->latest('id')
            ->get();

        // 3. Filter & score results
        $scored = [];

        foreach ($products as $product) {
            $title = mb_strtolower($product->title);
            $desc = mb_strtolower($product->description ?? '');
            $districtName = mb_strtolower($product->district?->name ?? '');

            $score = 0;
            $matchedAllTerms = true;

            foreach ($terms as $term) {
                $termLen = mb_strlen($term);

                // Exact whole word pattern
                $exactWordPattern = '/(^|[^' . $wordCharClass . '])' . preg_quote($term, '/') . '([^' . $wordCharClass . ']|$)/ui';

                // Word prefix pattern (handling short roots like "ish", "uy" without matching suffixes like "-ish")
                if ($term === 'ish') {
                    // Match: ish, ishchi, ishchilar, ishga, ishda, ishdan, ishlar, ishsiz, ishxona, ish joyi
                    // Reject: g'isht, kelishildi, berish, sotish, qilish, yuvish, ishonch
                    $wordPrefixPattern = '/(^|[^' . $wordCharClass . '])ish(chi|chilar|ga|da|dan|lar|siz|xona|joyi|o\'rni|\b|[^' . $wordCharClass . ']|$)/ui';
                } elseif ($termLen <= 3) {
                    $wordPrefixPattern = '/(^|[^' . $wordCharClass . '])' . preg_quote($term, '/') . '([^' . $wordCharClass . ']|$|da|ga|dan|lar|ning)/ui';
                } else {
                    $wordPrefixPattern = '/(^|[^' . $wordCharClass . '])' . preg_quote($term, '/') . '/ui';
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
                $scored[] = [
                    'product' => $product,
                    'score' => $score,
                ];
            }
        }

        // Sort by score DESC, then ID DESC
        usort($scored, function ($a, $b) {
            if ($b['score'] === $a['score']) {
                return $b['product']->id <=> $a['product']->id;
            }
            return $b['score'] <=> $a['score'];
        });

        return collect(array_column($scored, 'product'));
    }
}

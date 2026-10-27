<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Ro'yxatlar uchun umumiy filtrlar (kategoriya sahifasi, qidiruv):
 *   city_id, price_from, price_to, with_image=1, root_category_id,
 *   params[ID]=qiymat (tanlov) yoki params[ID][from]/[to] (son oralig'i), sort.
 */
class ProductFilters
{
    public const SORTS = ['new', 'cheap', 'expensive', 'popular'];

    public static function apply(Builder $query, Request $request): Builder
    {
        $query
            ->when($request->integer('city_id'), fn (Builder $q, int $cityId) => $q->where('products.district_id', $cityId))
            ->when($request->integer('price_from'), fn (Builder $q, int $from) => $q->where('products.price', '>=', $from))
            ->when($request->integer('price_to'), fn (Builder $q, int $to) => $q->where('products.price', '<=', $to))
            ->when($request->boolean('with_image'), fn (Builder $q) => $q->has('images'))
            ->when($request->integer('root_category_id'), function (Builder $q, int $rootId): void {
                $ids = array_keys(array_filter(Category::rootIdMap(), fn (int $root) => $root === $rootId));
                $q->whereIn('products.category_id', $ids ?: [0]);
            });

        foreach ((array) $request->input('params', []) as $parameterId => $value) {
            if (!is_numeric($parameterId)) {
                continue;
            }

            // Son oralig'i: yil, yurgan masofa, maydon, xonalar...
            if (is_array($value)) {
                $from = is_numeric($value['from'] ?? null) ? (float) $value['from'] : null;
                $to = is_numeric($value['to'] ?? null) ? (float) $value['to'] : null;
                if ($from === null && $to === null) {
                    continue;
                }

                $query->whereHas('parameter_values', function (Builder $q) use ($parameterId, $from, $to): void {
                    $q->where('parameter_id', (int) $parameterId)
                        ->when($from !== null, fn (Builder $q) => $q->whereRaw('CAST(value AS DECIMAL(15,2)) >= ?', [$from]))
                        ->when($to !== null, fn (Builder $q) => $q->whereRaw('CAST(value AS DECIMAL(15,2)) <= ?', [$to]));
                });

                continue;
            }

            // Tanlov: aniq qiymat ("Avtomat", "Yangi")
            if (is_string($value) && trim($value) !== '') {
                $query->whereHas('parameter_values', fn (Builder $q) => $q
                    ->where('parameter_id', (int) $parameterId)
                    ->where('value', trim($value)));
            }
        }

        return $query;
    }

    public static function sort(Builder $query, ?string $sort): Builder
    {
        match ($sort) {
            // Narxsiz e'lonlar ("kelishiladi") har doim oxirida
            'cheap' => $query->orderByRaw('products.price IS NULL')->orderBy('products.price'),
            'expensive' => $query->orderByRaw('products.price IS NULL')->orderByDesc('products.price'),
            'popular' => $query->orderByDesc('products.views_count'),
            default => $query->latest('products.published_at'),
        };

        return $query->latest('products.id');
    }
}

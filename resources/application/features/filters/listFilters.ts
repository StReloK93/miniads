/**
 * Ro'yxat filtrlari (kategoriya sahifasi, qidiruv). Backend: App\Services\ProductFilters.
 * Axios ichma-ich obyektni `params[10][from]=2015` ko'rinishida yuboradi, null qiymatlarni tashlab ketadi.
 */
export type SortKey = "new" | "cheap" | "expensive" | "popular";

export interface RangeValue {
   from?: number | null;
   to?: number | null;
}

export interface ListFilters {
   sort?: SortKey | null;
   city_id?: number | null;
   root_category_id?: number | null;
   price_from?: number | null;
   price_to?: number | null;
   with_image?: boolean;
   /** Kategoriya parametrlari: tanlov — qiymat, son — oraliq */
   params?: Record<number, string | RangeValue | null>;
}

export const SORT_OPTIONS: { value: SortKey; label: string }[] = [
   { value: "new", label: "sort.new" },
   { value: "cheap", label: "sort.cheap" },
   { value: "expensive", label: "sort.expensive" },
   { value: "popular", label: "sort.popular" },
];

function isFilled(value: unknown): boolean {
   if (value === null || value === undefined || value === "" || value === false) return false;
   if (typeof value === "object") return Object.values(value as object).some(isFilled);
   return true;
}

/** Tanlangan filtrlar soni (tugmadagi nishon uchun); saralash hisobga olinmaydi */
export function countActiveFilters(filters: ListFilters): number {
   const { sort, params, price_from, price_to, ...rest } = filters;
   let count = Object.values(rest).filter(isFilled).length;
   if (isFilled(price_from) || isFilled(price_to)) count++;
   count += Object.values(params ?? {}).filter(isFilled).length;
   return count;
}

/** So'rov uchun: bo'sh maydonlarsiz nusxa */
export function toQueryParams(filters: ListFilters): Record<string, unknown> {
   const query: Record<string, unknown> = {};
   for (const [key, value] of Object.entries(filters)) {
      if (key === "params") continue;
      if (isFilled(value)) query[key] = value === true ? 1 : value;
   }

   const params = Object.fromEntries(Object.entries(filters.params ?? {}).filter(([, value]) => isFilled(value)));
   if (Object.keys(params).length) query.params = params;

   return query;
}

export function emptyFilters(): ListFilters {
   return { sort: null, city_id: null, root_category_id: null, price_from: null, price_to: null, with_image: false, params: {} };
}

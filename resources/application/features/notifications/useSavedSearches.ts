import { ref } from "vue";
import ProfileRepo from "@shared/entities/Profile/ProfileRepo";
import { useCity } from "@shared/entities/District/useCity";
import { useCategory } from "@shared/entities/Category/useCategory";
import { formatPrice } from "@shared/modules/formatters";
import { t } from "@shared/i18n";
import { ICategory, ISavedSearch } from "@shared/types";
import { countActiveFilters, toQueryParams, type ListFilters } from "@/features/filters/listFilters";

// Ilova bo'ylab bitta ro'yxat: qidiruvdagi "Kuzatish" tugmasi va profil bir xil holatni ko'radi
const items = ref<ISavedSearch[] | null>(null);
let loading: Promise<void> | null = null;

// MySQL JSON ustuni kalitlar tartibini o'zgartiradi — taqqoslashda kalitlar saralanadi, sonlar satrga keltiriladi
function stable(value: unknown): string {
   if (value && typeof value === "object") {
      return `{${Object.keys(value)
         .sort()
         .map((key) => `${key}:${stable((value as Record<string, unknown>)[key])}`)
         .join(",")}}`;
   }
   return String(value);
}

/** Taqqoslash uchun: bir xil so'rov + filtrlar = bitta kuzatuv */
function signature(query: string, params: Record<string, unknown> | null | undefined) {
   const { sort: _sort, ...rest } = (params ?? {}) as Record<string, unknown>;
   return `${query.trim().toLowerCase()}|${stable(rest)}`;
}

export function useSavedSearches() {
   const cityStore = useCity();
   const categoryStore = useCategory();

   function load(force = false) {
      if (items.value && !force) return Promise.resolve();
      loading ??= ProfileRepo.savedSearches()
         .then(({ data }) => {
            items.value = data;
         })
         .finally(() => (loading = null));
      return loading;
   }

   function find(query: string, filters: ListFilters) {
      const key = signature(query, toQueryParams(filters));
      return items.value?.find((item) => signature(item.query, item.params) === key) ?? null;
   }

   /** "Cobalt · 100 mln gacha · Zarafshon" — bot xabari va profil ro'yxati uchun */
   function describe(query: string, filters: ListFilters) {
      const parts = [query.trim()];
      const { price_from: from, price_to: to } = filters;
      if (from && to) parts.push(`${formatPrice(from)}–${formatPrice(to)}`);
      else if (to) parts.push(t("watch.priceTo", { price: formatPrice(to) }));
      else if (from) parts.push(t("watch.priceFrom", { price: formatPrice(from) }));

      const city = cityStore.cities?.find((item) => item.id === filters.city_id);
      if (city && city.id) parts.push(city.name);

      const category = (categoryStore.parentCategories as ICategory[] | undefined)?.find(
         (item) => item.id === filters.root_category_id,
      );
      if (category) parts.push(category.name);
      if (filters.with_image) parts.push(t("watch.withImage"));

      // Parametrlar (yil, probeg...) nomlarini bu yerda bilmaymiz — soni yetarli
      const paramCount = Object.values(filters.params ?? {}).filter(Boolean).length;
      if (paramCount) parts.push(t("watch.params", { n: paramCount }));

      return parts.join(" · ").slice(0, 200);
   }

   async function add(query: string, filters: ListFilters) {
      const payload = {
         query: query.trim(),
         label: describe(query, filters),
         filters: { ...filters, sort: null },
         params: countActiveFilters(filters) ? toQueryParams({ ...filters, sort: null }) : null,
      };
      const { data } = await ProfileRepo.createSavedSearch(payload);
      items.value = [data, ...(items.value ?? [])];
      return data;
   }

   async function remove(id: number) {
      await ProfileRepo.deleteSavedSearch(id);
      items.value = (items.value ?? []).filter((item) => item.id !== id);
   }

   async function setPaused(id: number, paused: boolean) {
      const { data } = await ProfileRepo.updateSavedSearch(id, paused);
      items.value = (items.value ?? []).map((item) => (item.id === id ? data : item));
   }

   return { items, load, find, add, remove, setPaused };
}

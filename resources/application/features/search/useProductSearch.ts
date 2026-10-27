import { computed, ref, watch } from "vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { useRecentSearches } from "@shared/composables/useRecentSearch";
import { IProduct } from "@shared/types";
import { countActiveFilters, emptyFilters, toQueryParams, type ListFilters } from "@/features/filters/listFilters";

export const POPULAR_TAGS = ["Kvartira", "Telefon", "Cobalt", "Gentra", "Ish", "Mebel"];

const DEBOUNCE_MS = 350;

/**
 * Qidiruv holati va amallari (bosh sahifadagi qidiruv rejimi uchun):
 * yozilganda 350ms kutib avtomatik qidiradi, Enter'da tarixga yozadi, filtr o'zgarsa qayta qidiradi.
 *
 * Miltillamaslik uchun:
 *  - faqat eng oxirgi so'rov javobi qabul qilinadi (eski, kech kelgan javob yangisini bosib ketmaydi);
 *  - yuklanayotganda oldingi natijalar o'chirilmaydi — ustida yengil indikator turadi.
 */
export function useProductSearch() {
   const { searches, addSearch, removeSearch, clear: clearHistory } = useRecentSearches();

   const products = ref<IProduct[] | null>(null);
   const isLoading = ref(false);
   const searchText = ref("");
   const activeSearchText = ref("");
   const filters = ref<ListFilters>(emptyFilters());

   const activeFilterCount = computed(() => countActiveFilters(filters.value));
   const hasFilters = computed(() => activeFilterCount.value > 0);

   let debounceTimer: ReturnType<typeof setTimeout> | undefined;
   let lastRequestId = 0;
   // pick() matnni o'zi qo'yadi va qidiruvni o'zi boshlaydi — watch uni qayta yubormasin
   let skipNextTextChange = false;

   async function run(text: string) {
      clearTimeout(debounceTimer);
      const requestId = ++lastRequestId;
      activeSearchText.value = text;
      isLoading.value = true;

      try {
         const { data } = await ProductRepo.search({ search: text, ...toQueryParams(filters.value) });
         if (requestId === lastRequestId) products.value = data;
      } catch (error) {
         console.error("Qidiruvda xatolik:", error);
      } finally {
         // Kechikkan eski so'rov tugashi yangisining indikatorini o'chirib qo'ymasin
         if (requestId === lastRequestId) isLoading.value = false;
      }
   }

   watch(searchText, (value) => {
      const trimmed = value.trim();
      clearTimeout(debounceTimer);

      if (!trimmed) {
         lastRequestId++; // yo'lda qolgan so'rov javobi endi kerak emas
         activeSearchText.value = "";
         products.value = null;
         isLoading.value = false;
         return;
      }

      if (skipNextTextChange) {
         skipNextTextChange = false;
         return;
      }

      // Bitta harfga qidirmaymiz; kutilayotgan so'rov bekor — indikator ham o'chadi
      if (trimmed.length < 2) {
         lastRequestId++;
         isLoading.value = false;
         return;
      }

      // Indikator darhol: 350ms kutish ham "qidirilmoqda" bo'lib ko'rinsin
      isLoading.value = true;
      debounceTimer = setTimeout(() => run(trimmed), DEBOUNCE_MS);
   });

   // Filtr oynasida "Ko'rsatish" bosilganda — joriy so'rov qayta bajariladi
   watch(
      filters,
      () => {
         if (activeSearchText.value) run(activeSearchText.value);
      },
      { deep: true },
   );

   function submit() {
      const trimmed = searchText.value.trim();
      if (!trimmed) return;
      addSearch(trimmed);
      run(trimmed);
   }

   /** Tarix yoki ommabop so'rovdan tanlash */
   function pick(text: string) {
      addSearch(text);
      run(text);
      if (searchText.value !== text) {
         skipNextTextChange = true;
         searchText.value = text;
      }
   }

   function resetFilters() {
      filters.value = emptyFilters();
   }

   /** Qidiruvdan chiqishda: hammasi boshlang'ich holatga */
   function reset() {
      clearTimeout(debounceTimer);
      lastRequestId++;
      searchText.value = "";
      activeSearchText.value = "";
      products.value = null;
      isLoading.value = false;
      filters.value = emptyFilters();
   }

   return {
      products,
      isLoading,
      searches,
      removeSearch,
      clearHistory,
      searchText,
      activeSearchText,
      filters,
      activeFilterCount,
      hasFilters,
      submit,
      pick,
      resetFilters,
      reset,
   };
}

export type ProductSearch = ReturnType<typeof useProductSearch>;

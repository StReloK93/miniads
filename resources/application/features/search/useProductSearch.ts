import { computed, ref, watch } from "vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import ProfileRepo from "@shared/entities/Profile/ProfileRepo";
import { useRecentSearches } from "@shared/composables/useRecentSearch";
import { IProduct, ISearchResult } from "@shared/types";
import { countActiveFilters, emptyFilters, toQueryParams, type ListFilters } from "@/features/filters/listFilters";

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
   /** Aniq natija kam bo'lsa: so'zlarning bir qismiga mos e'lonlar ("hamshira ish" → «ish») */
   const related = ref<ISearchResult["related"]>(null);
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

   /*
      Admin statistikasi ("nima qidirilyapti, nima topilmayapti"): har harf emas, faqat "o'rnashgan" so'rov —
      Enter/chip darhol, yozib to'xtaganda 2.5 soniyadan keyin. Bir so'rov ketma-ket ikki marta yozilmaydi.
   */
   let logTimer: ReturnType<typeof setTimeout> | undefined;
   let lastLogged = "";
   function scheduleLog(text: string, count: number, delay: number) {
      clearTimeout(logTimer);
      logTimer = setTimeout(() => {
         if (text === lastLogged) return;
         lastLogged = text;
         ProfileRepo.logSearch(text, count).catch(() => {});
      }, delay);
   }

   async function run(text: string, commit = false) {
      clearTimeout(debounceTimer);
      const requestId = ++lastRequestId;
      activeSearchText.value = text;
      isLoading.value = true;

      try {
         const { data } = await ProductRepo.search({ search: text, ...toQueryParams(filters.value) });
         if (requestId === lastRequestId) {
            products.value = data.products;
            related.value = data.related;
            // Statistikada "topilmadi" — aniq mos e'lon yo'q bo'lsa (o'xshashlari bo'lsa ham)
            scheduleLog(text, data.products.length, commit ? 0 : 2500);
         }
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
      clearTimeout(logTimer);

      if (!trimmed) {
         lastRequestId++; // yo'lda qolgan so'rov javobi endi kerak emas
         activeSearchText.value = "";
         products.value = null;
         related.value = null;
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
      run(trimmed, true);
   }

   /** Tarix yoki ommabop so'rovdan tanlash */
   function pick(text: string) {
      addSearch(text);
      run(text, true);
      if (searchText.value !== text) {
         skipNextTextChange = true;
         searchText.value = text;
      }
   }

   function resetFilters() {
      filters.value = emptyFilters();
   }

   /** "Filtrga Uchquduqni qo'yish": qolgan filtrlar o'zgarmaydi, qidiruv qayta bajariladi */
   function setCity(cityId: number) {
      filters.value = { ...filters.value, city_id: cityId };
   }

   /** Qidiruvdan chiqishda: hammasi boshlang'ich holatga */
   function reset() {
      clearTimeout(debounceTimer);
      clearTimeout(logTimer);
      lastRequestId++;
      searchText.value = "";
      activeSearchText.value = "";
      products.value = null;
      related.value = null;
      isLoading.value = false;
      filters.value = emptyFilters();
   }

   return {
      products,
      related,
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
      setCity,
      reset,
   };
}

export type ProductSearch = ReturnType<typeof useProductSearch>;

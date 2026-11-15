<template>
   <!--
      Bosh sahifaning qidiruv rejimidagi kontent: tarix/ommabop → yuklanish → natijalar yoki "topilmadi".
      Chiplarda @mousedown.prevent: bosilganda maydon fokusni yo'qotmaydi (aks holda qidiruv rejimi yopilardi).
   -->
   <div>
      <!-- Qidiruv barcha shaharlar bo'yicha; tanlangan shaharni filtrga qo'yishni taklif qilamiz -->
      <div v-if="showCityHint" class="city-hint mb-3" @mousedown.prevent>
         <MapPin class="size-4 shrink-0 text-(--z-primary)" />
         <p class="flex-1 text-xs leading-snug">{{ $t("search.cityHint", { city: userCity!.name }) }}</p>
         <div class="flex shrink-0 items-center gap-1">
            <button type="button" class="city-hint-apply" @click="applyCity">
               {{ $t("search.cityApply", { city: userCity!.name }) }}
            </button>
            <button
               type="button"
               class="size-7 grid place-items-center rounded-full text-(--z-muted-text) hover:bg-(--z-muted)"
               :aria-label="$t('common.close')"
               @click="cityHintDismissed = true"
            >
               <X class="size-3.5" />
            </button>
         </div>
      </div>

      <!-- Natija bor bo'lsa — u joyida qoladi (yangi so'rovda faqat xiralashadi); skelet faqat birinchi qidiruvda -->
      <template v-if="search.products?.length || relatedProducts.length">
         <div v-if="search.products?.length" class="pb-2.5 flex flex-wrap items-center justify-between gap-x-2 gap-y-1.5">
            <p class="text-xs text-(--z-muted-text) tabular-nums">
               {{ search.isLoading ? $t("search.searching") : $t("search.found", search.products.length) }}
            </p>
            <WatchSearchButton :query="search.activeSearchText" :filters="search.filters" />
         </div>

         <!-- Aniq mos yo'q, lekin so'zlarning biriga mos e'lonlar bor: avval buni ochiq aytamiz -->
         <div v-else class="mb-3 rounded-2xl border border-(--z-border) bg-(--z-card) p-3.5">
            <div class="flex items-start gap-2.5">
               <SearchX class="size-4 mt-0.5 shrink-0 text-(--z-muted-text)" />
               <p class="text-sm font-medium leading-snug">
                  {{ $t("search.noExact", { query: search.activeSearchText }) }}
               </p>
            </div>
            <WatchSearchButton class="mt-3" :query="search.activeSearchText" :filters="search.filters" />
         </div>

         <div class="flex flex-col gap-2.5 transition-opacity duration-200" :class="{ 'opacity-50': search.isLoading }">
            <ProductCard v-for="product in search.products ?? []" :key="product.id" :product="product" />

            <template v-if="relatedProducts.length">
               <h4 class="related-title" :class="{ 'mt-3': search.products?.length }">
                  {{ $t(search.products?.length ? "search.relatedMore" : "search.related", { terms: relatedTerms }) }}
               </h4>
               <ProductCard v-for="product in relatedProducts" :key="`r-${product.id}`" :product="product" />
            </template>
         </div>
      </template>

      <div v-else-if="search.isLoading" class="flex flex-col gap-2.5">
         <ProductCardSkeleton v-for="n in 3" :key="n" />
      </div>

      <div v-else-if="search.activeSearchText" class="py-12 flex flex-col items-center text-center px-4">
         <div class="size-14 rounded-full bg-(--z-card) border border-(--z-border) grid place-items-center mb-3 text-(--z-muted-text)">
            <SearchX class="size-6" />
         </div>
         <h3 class="text-base font-semibold mb-1">{{ $t("common.notFound") }}</h3>
         <p class="text-xs text-(--z-muted-text) max-w-xs">
            {{ $t("search.noResultsFor", { query: search.activeSearchText }) }}
            {{ search.hasFilters ? $t("search.tryOtherOrFilters") : $t("search.tryOther") }}
         </p>
         <!-- Topilmadi — eng yaxshi payt kuzatuv taklif qilish uchun: talab bor, e'lon hali yo'q -->
         <WatchSearchButton class="mt-5" variant="cta" :query="search.activeSearchText" :filters="search.filters" />
         <BaseButton v-if="search.hasFilters" severity="secondary" size="sm" class="mt-3" @click="search.resetFilters()">
            {{ $t("filters.reset") }}
         </BaseButton>
      </div>

      <div v-else class="flex flex-col gap-6 pt-1">
         <section v-if="search.searches.length">
            <div class="flex justify-between items-center mb-2.5 text-xs">
               <span class="flex items-center gap-1.5 font-medium text-(--z-muted-text)">
                  <History class="size-3.5" />
                  {{ $t("search.recent") }}
               </span>
               <button
                  type="button"
                  class="text-(--z-muted-text) cursor-pointer active:opacity-70"
                  @mousedown.prevent
                  @click="search.clearHistory()"
               >
                  {{ $t("common.clear") }}
               </button>
            </div>
            <div class="flex flex-wrap gap-2">
               <div
                  v-for="item in search.searches"
                  :key="item"
                  class="search-chip pr-1.5"
                  @mousedown.prevent
                  @click="search.pick(item)"
               >
                  <span>{{ item }}</span>
                  <button
                     type="button"
                     class="size-5 rounded-full grid place-items-center text-(--z-muted-text) hover:bg-(--z-muted)"
                     :aria-label="$t('common.delete')"
                     @mousedown.prevent
                     @click.stop="search.removeSearch(item)"
                  >
                     <X class="size-3" />
                  </button>
               </div>
            </div>
         </section>

         <section>
            <div class="flex items-center gap-1.5 mb-2.5 text-xs font-medium text-(--z-muted-text)">
               <Sparkles class="size-3.5" />
               {{ $t("search.popular") }}
            </div>
            <div class="flex flex-wrap gap-2">
               <button
                  v-for="tag in popularTags"
                  :key="tag"
                  type="button"
                  class="search-chip"
                  @mousedown.prevent
                  @click="search.pick(tag)"
               >
                  {{ tag }}
               </button>
            </div>
         </section>
      </div>
   </div>
</template>

<script setup lang="ts">
import { computed, type UnwrapNestedRefs } from "vue";
import { useI18n } from "vue-i18n";
import { History, MapPin, SearchX, Sparkles, X } from "lucide-vue-next";
import ProductCard from "@/features/product/ProductCard.vue";
import ProductCardSkeleton from "@/features/product/ProductCardSkeleton.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import WatchSearchButton from "@/features/notifications/WatchSearchButton.vue";
import type { ProductSearch } from "@/features/search/useProductSearch";
import { useAuth } from "@shared/store/useAuth";

const props = defineProps<{ search: UnwrapNestedRefs<ProductSearch> }>();

// Har tilda o'z ro'yxati: ruscha "Квартира" ham o'zbekcha e'lonlarni topadi (qidiruv kirillni lotinga o'giradi)
const { t } = useI18n();
const popularTags = computed(() => t("search.popularTags").split(","));

const relatedProducts = computed(() => props.search.related?.products ?? []);
const relatedTerms = computed(() => (props.search.related?.terms ?? []).join(", "));

/* Qidiruv barcha shaharlar bo'yicha: tanlangan shahar bo'lsa — filtrga qo'yishni taklif qilamiz */
const authStore = useAuth();
const userCity = computed(() => authStore.user?.active_district ?? null);
// Yopilsa ilova qayta ochilguncha qayta chiqmaydi
const cityHintDismissed = cityHintDismissedState;
const showCityHint = computed(
   () => Boolean(userCity.value && props.search.activeSearchText && !props.search.filters.city_id && !cityHintDismissed.value),
);

function applyCity() {
   props.search.setCity(userCity.value!.id);
}
</script>

<script lang="ts">
import { ref } from "vue";

// Modul darajasida: qidiruvdan chiqib-kirganda ham yopilgan holat saqlanadi
const cityHintDismissedState = ref(false);
</script>

<style scoped>
.search-chip {
   display: inline-flex;
   height: 2.125rem;
   align-items: center;
   gap: 0.375rem;
   border: 1px solid var(--z-glass-border);
   border-radius: 999px;
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
   padding: 0 0.875rem;
   color: var(--z-foreground);
   font-size: 0.8125rem;
   font-weight: 500;
   cursor: pointer;
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.96);
   }
}

.city-hint {
   display: flex;
   flex-wrap: wrap;
   align-items: center;
   gap: 0.5rem 0.625rem;
   border: 1px solid var(--z-glass-border);
   border-radius: 1rem;
   background: var(--z-card);
   box-shadow: var(--z-glass-edge, none);
   padding: 0.625rem 0.5rem 0.625rem 0.875rem;
}

.city-hint-apply {
   height: 1.875rem;
   border-radius: 999px;
   background: var(--z-primary);
   padding: 0 0.75rem;
   color: var(--z-primary-foreground);
   font-size: 0.75rem;
   font-weight: 600;
   white-space: nowrap;
   cursor: pointer;
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.96);
   }
}

.related-title {
   padding-top: 0.25rem;
   color: var(--z-muted-text);
   font-size: 0.75rem;
   font-weight: 600;
   letter-spacing: 0.02em;
}
</style>

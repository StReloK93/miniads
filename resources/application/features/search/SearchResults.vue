<template>
   <!--
      Bosh sahifaning qidiruv rejimidagi kontent: tarix/ommabop → yuklanish → natijalar yoki "topilmadi".
      Chiplarda @mousedown.prevent: bosilganda maydon fokusni yo'qotmaydi (aks holda qidiruv rejimi yopilardi).
   -->
   <div>
      <!-- Natija bor bo'lsa — u joyida qoladi (yangi so'rovda faqat xiralashadi); skelet faqat birinchi qidiruvda -->
      <template v-if="search.products?.length">
         <p class="pb-2.5 text-xs text-(--z-muted-text) tabular-nums">
            {{ search.isLoading ? "Qidirilmoqda…" : `${search.products.length} ta e'lon topildi` }}
         </p>
         <div class="flex flex-col gap-2.5 transition-opacity duration-200" :class="{ 'opacity-50': search.isLoading }">
            <ProductCard v-for="product in search.products" :key="product.id" :product="product" />
         </div>
      </template>

      <div v-else-if="search.isLoading" class="flex flex-col gap-2.5">
         <ProductCardSkeleton v-for="n in 3" :key="n" />
      </div>

      <div v-else-if="search.activeSearchText" class="py-12 flex flex-col items-center text-center px-4">
         <div class="size-14 rounded-full bg-(--z-card) border border-(--z-border) grid place-items-center mb-3 text-(--z-muted-text)">
            <SearchX class="size-6" />
         </div>
         <h3 class="text-base font-semibold mb-1">Hech narsa topilmadi</h3>
         <p class="text-xs text-(--z-muted-text) max-w-xs">
            "<b>{{ search.activeSearchText }}</b>" bo'yicha e'lon yo'q. Boshqacha yozib ko'ring
            <template v-if="search.hasFilters"> yoki filtrlarni tozalang</template>.
         </p>
         <BaseButton v-if="search.hasFilters" severity="secondary" size="sm" class="mt-4" @click="search.resetFilters()">
            Filtrlarni tozalash
         </BaseButton>
      </div>

      <div v-else class="flex flex-col gap-6 pt-1">
         <section v-if="search.searches.length">
            <div class="flex justify-between items-center mb-2.5 text-xs">
               <span class="flex items-center gap-1.5 font-medium text-(--z-muted-text)">
                  <History class="size-3.5" />
                  Oxirgi qidiruvlar
               </span>
               <button
                  type="button"
                  class="text-(--z-muted-text) cursor-pointer active:opacity-70"
                  @mousedown.prevent
                  @click="search.clearHistory()"
               >
                  Tozalash
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
                     aria-label="O'chirish"
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
               Ommabop qidiruvlar
            </div>
            <div class="flex flex-wrap gap-2">
               <button
                  v-for="tag in POPULAR_TAGS"
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
import type { UnwrapNestedRefs } from "vue";
import { History, SearchX, Sparkles, X } from "lucide-vue-next";
import ProductCard from "@/features/product/ProductCard.vue";
import ProductCardSkeleton from "@/features/product/ProductCardSkeleton.vue";
import BaseButton from "@shared/ui/BaseButton.vue";
import { POPULAR_TAGS, type ProductSearch } from "@/features/search/useProductSearch";

defineProps<{ search: UnwrapNestedRefs<ProductSearch> }>();
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
</style>

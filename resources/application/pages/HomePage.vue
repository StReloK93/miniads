<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']" @scroll="onScroll">
      <template #header>
         <!-- Qidiruv rejimida shahar qatori va bo'limlar yig'iladi, maydon tepaga "suzib" chiqadi -->
         <div class="home-collapse" :class="{ 'is-closed': searching }">
            <div><DistrictSwitcher @district-changed="onDistrictChanged" /></div>
         </div>
         <HomeSearchBar
            ref="searchBar"
            v-model="search.searchText"
            :active="searching"
            :filter-count="search.activeFilterCount"
            :loading="search.isLoading"
            @activate="enterSearch"
            @blur="onSearchBlur"
            @cancel="exitSearch"
            @submit="search.submit()"
            @filters="isFilterOpen = true"
         />
         <div class="home-collapse" :class="{ 'is-closed': searching }">
            <div><CategoriesSlider /></div>
         </div>
      </template>

      <template #content>
         <ProductFilterDrawer v-model="search.filters" :open="isFilterOpen" with-category @close="isFilterOpen = false" />

         <!-- Almashinuv: natijalar tez so'nadi (joy band qilmay), lenta biroz kechikib pastdan ko'tariladi -->
         <Transition name="search-swap">
            <SearchResults v-if="searching" :search="search" />
         </Transition>

         <!-- Lenta qidiruv paytida yashiriladi (v-show): qaytganda qayta yuklanmaydi, joyi saqlanadi -->
         <Transition name="feed-return">
         <div v-show="!searching" ref="feed">
         <!-- Dastlabki yuklanish skeletlari -->
         <main v-if="isLoadingInitial" class="flex flex-col gap-2.5">
            <ProductCardSkeleton v-for="n in 3" :key="`skeleton-${n}`" />
         </main>

         <!-- Bo'sh holat -->
         <main
            v-else-if="products.length === 0"
            class="flex flex-col items-center justify-center py-16 text-center text-(--z-muted-text)"
         >
            <div
               class="size-14 rounded-full bg-(--z-card) border border-(--z-border) flex items-center justify-center mb-3"
            >
               <PackageOpen class="size-6 text-(--z-muted-text)" />
            </div>
            <p class="font-medium text-sm text-(--z-foreground)">Hozircha e'lonlar mavjud emas</p>
            <p class="text-xs mt-1 max-w-60">
               Tanlangan hudud bo'yicha e'lonlar topilmadi. Shaharni o'zgartirib ko'ring.
            </p>
         </main>

         <!-- E'lonlar ro'yxati (Cheksiz scroll) -->
         <main v-else class="flex flex-col gap-2.5">
            <ProductCard v-for="product in products" :key="product.id" :product="product" />

            <!-- Infinite Scroll Triggiri va Yuklanish animatsiyasi -->
            <div ref="loadMoreTrigger" class="py-2 flex items-center justify-center min-h-10">
               <div v-if="isLoadingMore" class="flex items-center gap-2 text-xs font-medium text-(--z-muted-text) py-2">
                  <span
                     class="size-4 border-2 border-(--z-primary) border-t-transparent rounded-full animate-spin"
                  ></span>
                  <span>Yana yuklanmoqda...</span>
               </div>
               <div
                  v-else-if="!hasMore && products.length > 0"
                  class="text-xs text-(--z-muted-text) py-4 text-center select-none"
               >
                  Barcha e'lonlar ko'rsatildi
               </div>
            </div>
         </main>
         </div>
         </Transition>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import DistrictSwitcher from "@/features/district/DistrictSwitcher.vue";
import ProductCardSkeleton from "@/features/product/ProductCardSkeleton.vue";
import CategoriesSlider from "@/features/category/CategoriesSlider.vue";
import HomeSearchBar from "@/features/search/HomeSearchBar.vue";
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import ProductCard from "@/features/product/ProductCard.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { IProduct } from "@shared/types";
import { PackageOpen } from "lucide-vue-next";
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { isTMA, postEvent } from "@tma.js/bridge";
import SearchResults from "@/features/search/SearchResults.vue";
import ProductFilterDrawer from "@/features/filters/ProductFilterDrawer.vue";
import { useProductSearch } from "@/features/search/useProductSearch";
import { pushBackHandler } from "@/modules/navigation";

const route = useRoute();
const router = useRouter();

/* ================= QIDIRUV REJIMI (Telegram uslubi) ================= */

const search = reactive(useProductSearch());
const searching = ref(false);
const isFilterOpen = ref(false);
const searchBar = ref<InstanceType<typeof HomeSearchBar> | null>(null);
const feed = ref<HTMLElement | null>(null);

let feedScrollTop = 0;
let removeBackHandler: (() => void) | null = null;

// Lenta va natijalar bitta skroll konteynerida (NavigationPageDecorator)
const scrollContainer = () => feed.value?.closest<HTMLElement>("aside") ?? null;

function setTelegramBackButton(visible: boolean) {
   if (isTMA()) postEvent("web_app_setup_back_button", { is_visible: visible });
}

function enterSearch() {
   if (searching.value) return;
   const container = scrollContainer();
   feedScrollTop = container?.scrollTop ?? 0;
   searching.value = true;
   if (container) container.scrollTop = 0;

   // Telegram "orqaga" tugmasi avval qidiruvni yopadi, ilovani emas
   removeBackHandler = pushBackHandler(exitSearch);
   setTelegramBackButton(true);
}

function exitSearch() {
   if (!searching.value) return;
   searching.value = false;
   isFilterOpen.value = false;
   search.reset();
   searchBar.value?.blur();

   removeBackHandler?.();
   removeBackHandler = null;
   setTelegramBackButton(false);

   // Lenta qaytgach — avvalgi joyiga
   nextTick(() => {
      const container = scrollContainer();
      if (container) container.scrollTop = feedScrollTop;
   });
}

// Bo'sh maydondan fokus ketsa — qidiruvdan chiqamiz (Telegram kabi); yozilgan bo'lsa natijalar qoladi
function onSearchBlur() {
   setTimeout(() => {
      if (!search.searchText.trim() && !search.activeSearchText && !isFilterOpen.value && searching.value) {
         exitSearch();
      }
   }, 120);
}

onBeforeUnmount(() => {
   removeBackHandler?.();
});

const products = ref<IProduct[]>([]);
const page = ref(1);
const hasMore = ref(true);
const isLoadingInitial = ref(true);
const isLoadingMore = ref(false);
const loadMoreTrigger = ref<HTMLElement | null>(null);

let observer: IntersectionObserver | null = null;

async function loadProducts(reset: boolean = false) {
   if (reset) {
      page.value = 1;
      hasMore.value = true;
      isLoadingInitial.value = true;
   } else {
      if (isLoadingMore.value || !hasMore.value) return;
      isLoadingMore.value = true;
   }

   try {
      const response = await ProductRepo.feed(page.value, 12);
      const newItems = response.data.data || [];

      if (reset) {
         products.value = newItems;
      } else {
         // Takroriy id'lar qo'shilib ketmasligi uchun filtrlash
         const existingIds = new Set(products.value.map((p) => p.id));
         const filtered = newItems.filter((p) => !existingIds.has(p.id));
         products.value = [...products.value, ...filtered];
      }

      hasMore.value = response.data.current_page < response.data.last_page;
      if (hasMore.value) {
         page.value += 1;
      }
   } catch (error) {
      console.error("E'lonlarni yuklashda xatolik:", error);
   } finally {
      isLoadingInitial.value = false;
      isLoadingMore.value = false;
      await nextTick();
      setupIntersectionObserver();
   }
}

function onDistrictChanged() {
   loadProducts(true);
}

function onScroll(event: Event) {
   const target = event.target as HTMLElement;
   // Qidiruv natijalarini varaqlaganda lenta yuklanmasin
   if (!target || searching.value) return;

   const distanceToBottom = target.scrollHeight - target.scrollTop - target.clientHeight;
   if (distanceToBottom < 350 && hasMore.value && !isLoadingMore.value && !isLoadingInitial.value) {
      loadProducts(false);
   }
}

function setupIntersectionObserver() {
   if (observer) {
      observer.disconnect();
   }

   if (!loadMoreTrigger.value) return;

   observer = new IntersectionObserver(
      (entries) => {
         if (entries[0]?.isIntersecting && hasMore.value && !isLoadingMore.value && !isLoadingInitial.value) {
            loadProducts(false);
         }
      },
      {
         rootMargin: "300px",
      },
   );

   observer.observe(loadMoreTrigger.value);
}

watch(loadMoreTrigger, (el) => {
   if (el) {
      setupIntersectionObserver();
   }
});

onMounted(() => {
   loadProducts(true);
   // Eski /search havolasi (bot, tarix) bosh sahifaning qidiruv rejimini ochadi
   if (route.query.search) {
      router.replace({ name: "home" });
      nextTick(() => searchBar.value?.focus());
   }
});

onBeforeUnmount(() => {
   if (observer) {
      observer.disconnect();
      observer = null;
   }
});
</script>

<style scoped>
/*
   Balandlikni silliq yig'ish: grid-template-rows 1fr → 0fr (aniq balandlik bilmasdan).
   Ichidagi element overflow:hidden bo'lishi shart.
*/
.home-collapse {
   display: grid;
   grid-template-rows: 1fr;
   transition:
      grid-template-rows 0.28s var(--page-ease, ease),
      opacity 0.2s ease;

   /* -mx-4/px-4: ichidagi chiplar qatori (CategoriesSlider) ekran chetigacha cho'zilishi qirqilmasin */
   > div {
      min-height: 0;
      overflow: hidden;
      margin: 0 -1rem;
      padding: 0 1rem;
   }

   &.is-closed {
      grid-template-rows: 0fr;
      opacity: 0;
      pointer-events: none;
   }
}

/* Qidiruv natijalari: kirishda yumshoq; chiqishda tez va joy band qilmaydi (lenta ostidan sakramasin) */
.search-swap-enter-active {
   transition:
      opacity 0.22s var(--page-ease, ease),
      transform 0.22s var(--page-ease, ease);
}
.search-swap-leave-active {
   position: absolute;
   inset: 0 1rem auto;
   transition: opacity 0.12s ease-in;
}
.search-swap-enter-from {
   opacity: 0;
   transform: translateY(8px);
}
.search-swap-leave-to {
   opacity: 0;
}

/* Lenta qaytishi: natijalar so'ngach pastdan ko'tariladi; ketishi esa darhol (qidiruv bilan ustma-ust turmasin) */
.feed-return-enter-active {
   transition:
      opacity 0.3s var(--page-ease, ease) 0.08s,
      transform 0.34s var(--page-ease, ease) 0.08s;
}
.feed-return-enter-from {
   opacity: 0;
   transform: translateY(14px);
}
.feed-return-leave-active {
   transition: none;
}

@media (prefers-reduced-motion: reduce) {
   .home-collapse {
      transition: opacity 0.15s ease;
   }

   .search-swap-enter-from,
   .feed-return-enter-from {
      transform: none;
   }
}
</style>

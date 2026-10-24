<template>
   <NavigationPageDecorator
      :header-class="['border-b', 'border-(--z-border)']"
      @scroll="onScroll"
   >
      <template #header>
         <BaseChangeDistrictModal @district-changed="onDistrictChanged" />
         <CategoriesSlider />
      </template>

      <template #content>
         <!-- Dastlabki yuklanish skeletlari -->
         <main v-if="isLoadingInitial" class="flex flex-col gap-4">
            <BaseSkeletonCard v-for="n in 3" :key="`skeleton-${n}`" />
         </main>

         <!-- Bo'sh holat -->
         <main
            v-else-if="products.length === 0"
            class="flex flex-col items-center justify-center py-16 text-center text-(--z-muted-text)"
         >
            <div class="size-14 rounded-full bg-(--z-card) border border-(--z-border) flex items-center justify-center mb-3">
               <PackageOpen class="size-6 text-(--z-muted-text)" />
            </div>
            <p class="font-medium text-sm text-(--z-foreground)">Hozircha e'lonlar mavjud emas</p>
            <p class="text-xs mt-1 max-w-[240px]">Tanlangan hudud bo'yicha e'lonlar topilmadi. Shaharni o'zgartirib ko'ring.</p>
         </main>

         <!-- E'lonlar ro'yxati (Cheksiz scroll) -->
         <main v-else class="flex flex-col gap-4">
            <BaseProductCard
               v-for="product in products"
               :key="product.id"
               :product="product"
            />

            <!-- Infinite Scroll Triggiri va Yuklanish animatsiyasi -->
            <div ref="loadMoreTrigger" class="py-2 flex items-center justify-center min-h-10">
               <div
                  v-if="isLoadingMore"
                  class="flex items-center gap-2 text-xs font-medium text-(--z-muted-text) py-2"
               >
                  <span class="size-4 border-2 border-(--z-primary) border-t-transparent rounded-full animate-spin"></span>
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
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import BaseChangeDistrictModal from "@/components/BaseChangeDistrictModal.vue";
import BaseSkeletonCard from "@/components/BaseSkeletonCard.vue";
import CategoriesSlider from "@/components/CategoriesSlider.vue";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import BaseProductCard from "@/components/BaseProductCard.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { IProduct } from "@shared/types";
import { PackageOpen } from "lucide-vue-next";
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

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
   if (!target) return;

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
      }
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
});

onBeforeUnmount(() => {
   if (observer) {
      observer.disconnect();
      observer = null;
   }
});
</script>

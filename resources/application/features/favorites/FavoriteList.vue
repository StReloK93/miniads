<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']">
      <template #header>
         <aside class="mb-4">
            <div class="flex items-center justify-between mb-2">
               <h3 class="font-bold text-xl">Sevimlilar</h3>
            </div>
            <p class="text-sm text-(--z-muted-text)">
               <template v-if="favorites">Sizda sevimlilar ro'yhatida {{ favorites.length }} ta e'lon mavjud.</template>
               <template v-else>Yuklanmoqda…</template>
            </p>
         </aside>
      </template>
      <template #content>
         <!-- Avval yuklanish (rasmlar ham tayyor bo'lguncha), keyin ro'yxat yoki bo'sh holat -->
         <Transition mode="out-in">
            <main v-if="isLoading || !imagesReady" class="flex flex-col gap-2.5">
               <ProductCardSkeleton v-for="n in 3" :key="n" />
            </main>
            <main v-else-if="favorites?.length" class="flex flex-col gap-2.5">
               <ProductCard v-for="product in favorites" :product="product" :key="product.id" />
            </main>
            <div v-else class="flex flex-col justify-center items-center py-14 text-center">
               <div class="size-14 mb-3 rounded-full grid place-items-center bg-(--z-card) border border-(--z-border)">
                  <Heart class="size-6 text-(--z-muted-text)" />
               </div>
               <h3 class="font-semibold mb-1">Sevimlilar bo'sh</h3>
               <p class="text-sm text-(--z-muted-text) max-w-64">
                  Yoqqan e'lonlarni yurakcha bilan belgilang — ular shu yerda saqlanadi.
               </p>
            </div>
         </Transition>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import ProductCardSkeleton from "@/features/product/ProductCardSkeleton.vue";
import ProductCard from "@/features/product/ProductCard.vue";
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import { preloadImages } from "@shared/modules/helpers";
import { useFetchDecorator } from "@shared/composables/useFetch";
import FavoriteRepo from "@shared/entities/Favorite/FavoriteRepo";
import { IProduct } from "@shared/types";
import { Heart } from "lucide-vue-next";
import { onMounted, ref } from "vue";

const imagesReady = ref(false);

const { data: favorites, execute: executeLatest, isLoading } = useFetchDecorator<IProduct[]>(FavoriteRepo.index);

onMounted(async () => {
   try {
      await executeLatest();
      // Kartada kichik nusxa ko'rsatiladi — shuni oldindan yuklaymiz
      const images = (favorites.value ?? [])
         .filter((product) => product.images?.length)
         .map((product) => `/storage/${product.images[0].thumb_src || product.images[0].src}`);
      await preloadImages(images);
   } finally {
      imagesReady.value = true;
   }
});
</script>

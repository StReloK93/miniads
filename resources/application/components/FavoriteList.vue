<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']">
      <template #header>
         <aside class="mb-4">
            <div class="flex items-center justify-between mb-2">
               <h3 class="font-bold text-xl">Sevimlilar</h3>
            </div>
            <p class="text-sm text-(--z-muted-text)">
               Sizda sevimlilar ro'yhatida {{ favorites?.length ?? 0 }} ta e'lon mavjud.
            </p>
         </aside>
      </template>
      <template #content>
         <Transition mode="out-in">
            <main v-if="fullLoadingImages && favorites?.length" class="flex flex-col gap-4">
               <BaseProductCard v-for="product in favorites" :product="product" :key="product.id" />
            </main>
            <div v-else-if="favorites?.length == 0" class="font-bold h-full flex items-center justify-center">
               Hech nima topilmadi
            </div>
            <main v-else class="flex flex-col gap-4">
               <BaseSkeletonCard v-for="n in 3" :key="n" />
            </main>
         </Transition>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import BaseSkeletonCard from "@/components/BaseSkeletonCard.vue";
import BaseProductCard from "@/components/BaseProductCard.vue";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import { preloadImages } from "@/modules/Helpers";
import { useFetchDecorator } from "@shared/composables/useFetch";
import FavoriteRepo from "@shared/entities/Favorite/FavoriteRepo";
import { IProduct } from "@shared/types";
import { onMounted, ref } from "vue";

const fullLoadingImages = ref<boolean>(false);

const { data: favorites, execute: executeLatest, isFirstLoading } = useFetchDecorator<IProduct[]>(FavoriteRepo.index);

onMounted(async () => {
   await executeLatest();

   const images = <any[]>[];
   favorites.value
      ?.filter((product) => product.images.length > 0)
      .forEach((product) => {
         const imageUrls = `/storage/${product.images[0].src}`;
         images.push(imageUrls);
      });

   await preloadImages(images);

   fullLoadingImages.value = true;
});
</script>

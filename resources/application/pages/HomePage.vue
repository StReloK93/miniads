<template>
   <div>
      <swiper :slides-per-view="3.3" :space-between="10">
         <template v-if="isImagesReady">
            <swiper-slide v-for="category in parentCategories" :key="category.name">
               <RouterLink :to="{ name: 'categories', query: { open: `${category.id}` } }">
                  <div
                     class="select-none aspect-square gap-2 flex flex-col items-center justify-center bg-(--color-surface-muted) rounded-2xl border border-(--color-border) p-1"
                  >
                     <img :src="`${category.image}`" class="md:w-16 w-10 dark:invert" />
                     <div class="text-[11px] font-semibold text-tertiary text-center">
                        {{ category.name }}
                     </div>
                  </div>
               </RouterLink>
            </swiper-slide>
         </template>
      </swiper>
      <main class="h-80">
         <BaseForm :submit="onload" :input-configs="productInputs" />
      </main>
   </div>
</template>

<script setup lang="ts">
import { productInputs } from "@/entities/Product/ProductInputs";
import BaseForm from "@components/BaseForm.vue";
async function onload() {}

import { Swiper, SwiperSlide } from "swiper/vue";
import CategoryRepo from "@/entities/Category/CategoryRepo";
import { onMounted, ref } from "vue";
import { preloadImages } from "@/modules/Helpers";
import { useFetchDecorator } from "@/modules/useFetch";
import { ICategory } from "@shared/types";
const { data: parentCategories, execute: fetchCategories } = useFetchDecorator<ICategory[]>(CategoryRepo.parents);
const isImagesReady = ref(false);
onMounted(async () => {
   await fetchCategories();
   if (parentCategories.value) {
      const imageUrls: string[] = parentCategories.value.map((category) => category.image);
      await preloadImages(imageUrls);
      isImagesReady.value = true;
   }
});
</script>

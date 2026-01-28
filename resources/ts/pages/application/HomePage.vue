<template>
   <div>
      <!-- <section class="flex justify-between items-center mb-1.5">
         <span class="font-semibold text-tertiary">Kategoriyalar</span>
         <Button
            as="router-link"
            :to="{ name: 'categories' }"
            icon="pi pi-angle-right"
            size="small"
            rounded
            severity="secondary"
         />
      </section> -->
      <swiper :slides-per-view="3.3" :space-between="10">
         <template v-if="isImagesReady">
            <swiper-slide v-for="category in parentCategories" :key="category.name">
               <RouterLink :to="{ name: 'categories', query: { open: `${category.id}` } }">
                  <div
                     class="select-none aspect-square gap-2 flex flex-col items-center justify-center bg-secondary rounded-2xl border border-secondary p-1"
                  >
                     <img :src="`${category.image}`" class="md:w-16 w-10 dark:invert" />
                     <div class="text-[11px] font-semibold text-tertiary text-center">
                        {{ category.name }}
                     </div>
                  </div>
               </RouterLink>
            </swiper-slide>
         </template>
         <template v-else>
            <swiper-slide v-for="n in 6" :key="n">
               <Skeleton class="mb-2 aspect-square" size="100%"></Skeleton>
            </swiper-slide>
         </template>
      </swiper>
      <!-- <section class="flex justify-between items-center my-1.5">
         <span class="font-semibold text-tertiary">Top e'lonlar</span>
         <Button icon="pi pi-angle-right" size="small" rounded severity="secondary" />
      </section> -->
   </div>
</template>

<script setup lang="ts">
import { Swiper, SwiperSlide } from "swiper/vue";
// import BaseProductCard from "@components/BaseProductCard.vue";
import CategoryRepo from "@/entities/Category/CategoryRepo";
import { onMounted, ref } from "vue";
import { preloadImages } from "@/modules/Helpers";
import { useFetchDecorator } from "@/modules/useFetch";
import { ICategory } from "@/types";
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

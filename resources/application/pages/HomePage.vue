<template>
   <section>
      <swiper :slidesPerView="'auto'" :space-between="10">
         <template v-if="isImagesReady">
            <swiper-slide v-for="category in parentCategories" :key="category.name" class="inline! w-auto!">
               <RouterLink :to="{ name: 'categories', query: { open: `${category.id}` } }" class="select-none">
                  <div
                     class="flex items-center justify-center gap-2 bg-(--color-surface-muted) rounded-md border border-(--color-border) py-2.5 px-4"
                  >
                     <!-- <img :src="`${category.image}`" class="md:w-16 w-10 dark:invert" /> -->
                     <HomeIcon v-if="category.id == 1" class="size-4 text-(--color-text-secondary)" />
                     <DevicePhoneMobileIcon v-if="category.id == 12" class="size-4 text-(--color-text-secondary)" />
                     <div class="text-[11px] font-semibold text-tertiary text-center">
                        {{ category.name }}
                     </div>
                  </div>
               </RouterLink>
            </swiper-slide>
         </template>
      </swiper>
   </section>
</template>

<script setup lang="ts">
import { Swiper, SwiperSlide } from "swiper/vue";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { onMounted, ref } from "vue";
import { preloadImages } from "@/modules/Helpers";
import { useFetchDecorator } from "@shared/api/useFetch";
import { ICategory } from "@shared/types";
import { DevicePhoneMobileIcon, HomeIcon, PhoneIcon } from "@heroicons/vue/24/solid";
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

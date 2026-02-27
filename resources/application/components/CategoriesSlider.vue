<template>
   <main class="pb-4 -mx-4">
      <div class="flex items-center text-sm justify-between mb-2 px-4">
         <h3 class="title text-xs">Bo'limlar</h3>
      </div>
      <swiper :slidesPerView="5.5" :space-between="10" :modules="[FreeMode]" class="w-full px-4!">
         <swiper-slide>
            <RouterLink :to="{ name: 'categories' }" class="select-none inline-flex flex-col items-center gap-2">
               <div
                  class="size-14 grid place-items-center bg-(--z-primary) text-(--z-muted) border border-(--z-border) rounded-full"
               >
                  <LayoutGrid class="size-5" stroke-width="1.5" />
               </div>

               <div class="text-xs text-center">Barchasi</div>
            </RouterLink>
         </swiper-slide>
         <swiper-slide v-for="category in CategoryStore.parentCategories" :key="category.id">
            <RouterLink
               :to="
                  category.is_page
                     ? { name: 'category', params: { id: category.id } }
                     : { name: 'categories', query: { category_id: category.id } }
               "
               class="select-none inline-flex flex-col items-center gap-2"
            >
               <div class="size-14 grid place-items-center bg-(--z-card) border border-(--z-border) rounded-full">
                  <component :is="icons[category.image]" stroke-width="1.5" class="size-5" />
               </div>

               <div class="text-xs text-center">
                  {{ category.name }}
               </div>
            </RouterLink>
         </swiper-slide>
      </swiper>
   </main>
</template>

<script setup lang="ts">
import { Swiper, SwiperSlide } from "swiper/vue";
import { LayoutGrid } from "lucide-vue-next";
import icons from "@/modules/icons";
import { FreeMode } from "swiper/modules";
import { useCategory } from "@shared/entities/Category/useCategory";

const CategoryStore = useCategory();
</script>

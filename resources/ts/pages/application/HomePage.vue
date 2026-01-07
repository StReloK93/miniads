<template>
   <div>
      <section class="flex justify-between items-center mb-1.5">
         <span class="font-semibold text-tertiary">Bo'limlar</span>
         <Button
            as="router-link"
            :to="{ name: 'categories' }"
            icon="pi pi-angle-right"
            size="small"
            rounded
            severity="secondary"
         />
      </section>
      <swiper :slides-per-view="3.3" :space-between="10">
         <template v-if="!isLoading">
            <swiper-slide v-for="(category, index) in parentCategories" :key="category.name">
               <RouterLink :to="{ name: 'categories', query: { open: `category_${index}` } }">
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
               <Skeleton class="mb-2 aspect-square" height="92px"></Skeleton>
            </swiper-slide>
         </template>
      </swiper>
      <section class="flex justify-between items-center my-1.5">
         <span class="font-semibold text-tertiary">Top e'lonlar</span>
         <Button icon="pi pi-angle-right" size="small" rounded severity="secondary" />
      </section>
      <swiper :slides-per-view="2.2" :space-between="10">
         <swiper-slide v-for="product in products" :key="product.title">
            <BaseProductCard
               :image="product.image"
               :title="product.title"
               :subtitle="product.subtitle"
               :price="product.price"
            />
         </swiper-slide>
      </swiper>
      <section class="flex justify-between items-center my-1.5">
         <span class="font-semibold text-tertiary">Yangiliklar</span>
         <Button icon="pi pi-angle-right" size="small" rounded severity="secondary" />
      </section>
      <swiper :slides-per-view="3.2" :space-between="10">
         <swiper-slide v-for="product in products" :key="product.title">
            <BaseProductCard
               :image="product.image"
               :title="product.title"
               :subtitle="product.subtitle"
               :price="product.price"
            />
         </swiper-slide>
      </swiper>
   </div>
</template>

<script setup lang="ts">
import { Swiper, SwiperSlide } from "swiper/vue";
import { products } from "@/test/data";
import BaseProductCard from "@components/BaseProductCard.vue";
import CategoryRepo from "@/repositories/CategoryRepo";

const { data: parentCategories, isLoading } = CategoryRepo.parents();
// function onSwiper(swiper) {
//    console.log(swiper);
// }
// function onSlideChange() {
//    console.log("slide change");
// }
</script>

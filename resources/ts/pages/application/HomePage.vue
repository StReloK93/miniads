<template>
   <div>
      <section class="flex justify-between items-center mb-1.5">
         <span class="font-semibold text-surface-700">Bo'limlar</span>
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
         <swiper-slide v-for="(category, index) in categories" :key="category.name">
            <RouterLink :to="{ name: 'categories', query: { open: `category_${index}` } }">
               <div
                  class="select-none aspect-square gap-2 flex flex-col items-center justify-center bg-surface-50 rounded-2xl border border-surface-100 p-2"
               >
                  <img :src="`${category.image}`" class="md:w-16 w-10" />
                  <div class="text-xs font-semibold text-surface-600">
                     {{ category.name }}
                  </div>
               </div>
            </RouterLink>
         </swiper-slide>
      </swiper>
      <section class="flex justify-between items-center my-1.5">
         <span class="font-semibold text-surface-700">Top e'lonlar</span>
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
         <span class="font-semibold text-surface-700">Yangiliklar</span>
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
import { ICategory } from "@/types";
import { Swiper, SwiperSlide } from "swiper/vue";
import { products } from "@/test/data";
import BaseProductCard from "@components/BaseProductCard.vue";
import { Ref, ref } from "vue";
import axios from "axios";

const categories: Ref<ICategory[]> = ref([]);
axios.get<ICategory[]>("api/categories").then((response) => {
   categories.value = response.data;
});
// function onSwiper(swiper) {
//    console.log(swiper);
// }
// function onSlideChange() {
//    console.log("slide change");
// }
</script>

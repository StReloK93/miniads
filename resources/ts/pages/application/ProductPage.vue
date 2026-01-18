<template>
   <section class="container mx-auto">
      <swiper :slides-per-view="1" :space-between="10">
         <swiper-slide v-for="item in 10" :key="item">
            <img :src="`https://picsum.photos/1200?random=${item}`" class="h-64 w-full object-cover" />
         </swiper-slide>
      </swiper>
      <div class="px-4">{{ product }}</div>
   </section>
</template>

<script setup lang="ts">
import ProductRepo from "@/entities/Product/ProductRepo";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useRoute } from "vue-router";
import { useFetchDecorator } from "@/modules/useFetch";
import { onMounted } from "vue";
const route = useRoute();

const { data: product, execute: executeProduct } = useFetchDecorator(ProductRepo.show);

onMounted(() => {
   executeProduct(route.params.id);
});
</script>

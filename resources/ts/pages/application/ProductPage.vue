<template>
   <section class="container mx-auto bg-tertiary h-dvh">
      <main class="relative">
         <div class="absolute top-0 z-10 pt-safe-top! px-2 w-full">
            <BackPreviusPage title="Elon">
               <Button icon="pi pi-heart" variant="text" severity="danger" />
            </BackPreviusPage>
         </div>
         <swiper :slides-per-view="1" :pagination="true" :modules="[Pagination]" :space-between="5">
            <swiper-slide v-for="image in product?.images" :key="image.id">
               <img :src="`/storage/${image.src}`" class="h-64 w-full object-cover" />
            </swiper-slide>
         </swiper>
      </main>
      <main class="px-4 py-2">
         <div class="flex justify-between items-center">
            <span class="font-semibold">
               {{ product?.title }}
            </span>
            <span class="font-semibold text-sky-500"> 100 so'm </span>
         </div>
         <div class="flex gap-3 flex-wrap py-3" v-if="product?.parameter_values.length">
            <Tag
               v-for="value in product?.parameter_values"
               :key="value.id"
               severity="secondary"
               class="rounded! font-normal! text-secondary!"
            >
               {{ value.parameter.placeholder }} - {{ value.value }}
            </Tag>
         </div>
         <hr class="border-px border-secondary -mx-5 mt-2" />
         <h3 class="mt-2 font-semibold">Izoh</h3>
         <div class="py-2 text-secondary">
            {{ product?.description }}
         </div>
      </main>
   </section>
</template>

<script setup lang="ts">
import BackPreviusPage from "@/components/BackPreviusPage.vue";
import ProductRepo from "@/entities/Product/ProductRepo";
import { Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useRoute } from "vue-router";
import { useFetchDecorator } from "@/modules/useFetch";
import { onMounted } from "vue";
import { IProduct } from "@/types";
const route = useRoute();

const { data: product, execute: executeProduct } = useFetchDecorator<IProduct>(ProductRepo.show);

onMounted(() => {
   executeProduct(route.params.id);
});
</script>

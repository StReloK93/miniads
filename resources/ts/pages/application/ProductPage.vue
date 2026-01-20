<template>
   <section class="container mx-auto bg-tertiary h-dvh relative">
      <div class="absolute top-0 z-20 pt-safe-top! p-2 w-full">
         <BackPreviusPage class="pt-2">
            <Button icon="pi pi-heart" rounded size="small" severity="secondary" />
         </BackPreviusPage>
      </div>

      <div v-if="!isImagesReady">
         <Skeleton height="256px" />
         <div class="p-4">
            <Skeleton height="1.3rem" width="80%" class="mb-3" />
            <div class="flex gap-2 mb-2">
               <Skeleton v-for="n in 3" :key="n" width="4rem" height="1.5rem" />
            </div>
            <Skeleton height="1rem" width="40%" class="ml-auto mb-2" />
            <hr class="border-secondary -mx-5" />
            <Skeleton height="0.8rem" width="30%" class="mt-4 mb-2" />
            <Skeleton v-for="n in 6" :key="n" height="0.8rem" class="mt-2" />
         </div>
      </div>

      <div v-else>
         <main class="relative">
            <swiper :modules="[Pagination]" pagination class="h-64">
               <swiper-slide v-for="image in product?.images" :key="image.id">
                  <img :src="`/storage/${image.src}`" class="h-full w-full object-cover" />
               </swiper-slide>
            </swiper>
         </main>

         <main class="px-4 py-3">
            <h1 class="font-semibold leading-4">{{ product?.title }}</h1>

            <div class="flex gap-2 flex-wrap py-3" v-if="product?.parameter_values.length">
               <Tag v-for="v in product.parameter_values" :key="v.id" severity="secondary" class="font-normal!">
                  {{ v.parameter.placeholder }} - {{ v.value }}
               </Tag>
            </div>

            <div class="text-sky-500 text-right pb-2 text-sm font-semibold">900 000 000 so'm</div>
            <hr class="border-secondary -mx-5 mt-1" />

            <h3 class="mt-2 font-semibold">Izoh</h3>
            <div class="py-2 text-secondary leading-5">{{ product?.description }}</div>
         </main>
      </div>
   </section>
</template>

<script setup lang="ts">
import BackPreviusPage from "@/components/BackPreviusPage.vue";
import ProductRepo from "@/entities/Product/ProductRepo";
import { Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useRoute } from "vue-router";
import { useFetchDecorator } from "@/modules/useFetch";
import { onMounted, ref } from "vue";
import { IProduct } from "@/types";
import { preloadImages } from "@/modules/Helpers";
const route = useRoute();

const { data: product, execute: executeProduct } = useFetchDecorator<IProduct>(ProductRepo.show);

const isImagesReady = ref(false);

onMounted(async () => {
   // 1. API'dan ma'lumotni olamiz
   await executeProduct(route.params.id);

   // 2. Agar rasmlar bo'lsa, ularni yuklaymiz
   if (product.value?.images?.length) {
      const imageUrls = product.value.images.map((img) => `/storage/${img.src}`);
      await preloadImages(imageUrls);
   }

   // 3. Hamma narsa tayyor bo'lgach, loadingni o'chiramiz
   isImagesReady.value = true;
});
</script>

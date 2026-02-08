<template>
   <section class="container mx-auto bg-tertiary h-dvh relative">
      <div class="absolute top-0 z-20 pt-safe-top! p-2 w-full">
         <BackPreviusPage class="pt-2">
            <BaseButton icon="pi pi-heart" iconOnly rounded severity="secondary">
               <template #icon>
                  <Heart class="size-5" />
               </template>
            </BaseButton>
         </BackPreviusPage>
      </div>

      <div>
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
               <span v-for="v in product.parameter_values" :key="v.id">
                  {{ v.parameter.placeholder }} - {{ v.value }}
               </span>
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
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useRoute } from "vue-router";
import { useFetchDecorator } from "@shared/api/useFetch";
import { onMounted, ref } from "vue";
import { IProduct } from "@shared/types";
import { preloadImages } from "@/modules/Helpers";
import { Heart } from "lucide-vue-next";
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

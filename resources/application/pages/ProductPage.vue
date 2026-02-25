<template>
   <section class="full-page flex flex-col">
      <aside class="grow relative">
         <main class="relative -mt-[calc(var(--safe-area-top)+var(--spacing)*4)]">
            <swiper :modules="[Pagination]" pagination class="h-64">
               <swiper-slide v-for="image in product?.images" :key="image.id">
                  <img :src="`/storage/${image.src}`" class="h-full w-full object-cover" />
               </swiper-slide>
            </swiper>
         </main>

         <main class="pt-5 px-4">
            <!--  -->

            <div
               class="inline-flex items-center gap-1 font-extrabold text-2xl mb-3"
               :class="{ 'flex-row-reverse': product?.price_type.position === 'left' }"
            >
               <span>
                  {{ formatPrice(product?.price) }}
               </span>
               <span>
                  {{ product?.price_type.type }}
               </span>
            </div>

            <!--  -->

            <h1 class="mb-4 text-xl font-medium">{{ product?.title }}</h1>

            <!--  -->
            <div class="text-(--z-secondary) text-xs flex gap-2 items-center mb-6">
               <span class="capitalize">
                  {{ timeAgo(product?.created_at!) }}
               </span>
               <span class="inline-block w-1 h-1 rounded-full bg-(--z-secondary)"> </span>
               <span class="flex items-center gap-1"> 1.2k ko'rildi </span>
               <span class="inline-block w-1 h-1 rounded-full bg-(--z-secondary)"> </span>

               <span class="flex items-center gap-1">
                  <MapPin class="size-3 inline" />
                  Uchquduq
               </span>
            </div>
            <!--  -->

            <!--  -->
            <h3 class="text-(--z-secondary) uppercase text-sm">Izoh</h3>
            <div class="py-1 leading-5 text-sm mb-6">{{ product?.description }}</div>

            <h3 v-if="product?.parameter_values.length" class="text-(--z-secondary) uppercase text-sm mb-2">
               Qo'shimcha ma'lumot
            </h3>
            <aside v-if="product?.parameter_values.length" class="text-sm divide-y divide-gray-50">
               <div v-for="v in product.parameter_values" :key="v.id" class="flex justify-between py-2">
                  <span class="text-(--z-secondary)">
                     {{ v.parameter.title }}
                  </span>
                  <main class="flex gap-1.5 font-medium">
                     <span>
                        {{ v.value }}
                     </span>
                     <span v-if="v.parameter.unit">
                        {{ v.parameter.unit }}
                     </span>
                  </main>
               </div>
            </aside>
         </main>
      </aside>
      <aside class="px-4 pt-4 border-t border-(--z-border) flex gap-4">
         <BaseButton @click="callPhone(product?.phone!)" severity="primary" class="grow">
            <template #icon>
               <Phone class="size-4 inline" />
            </template>
            Qo'ng'iroq qilish
         </BaseButton>
         <BaseButton severity="secondary" iconOnly class="aspect-square">
            <template #icon>
               <Heart class="size-5 inline" />
            </template>
         </BaseButton>
      </aside>
   </section>
</template>

<script setup lang="ts">
import { timeAgo } from "@/modules/Helpers";
import { isTMA } from "@tma.js/bridge";
import { formatPrice } from "@/modules/Helpers";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useRoute } from "vue-router";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { onMounted, ref } from "vue";
import { IProduct } from "@shared/types";
import { preloadImages } from "@/modules/Helpers";
import { Eye, Heart, MapPin, Phone } from "lucide-vue-next";
const route = useRoute();

const { data: product, execute: executeProduct } = useFetchDecorator<IProduct>(ProductRepo.show);

const isImagesReady = ref(false);

function callPhone(phone: string) {
   if (isTMA()) {
      window.open(`tel:${phone}`);
   }
}

onMounted(async () => {
   await executeProduct(route.params.id);

   if (product.value?.images?.length) {
      const imageUrls = product.value.images.map((img) => `/storage/${img.src}`);
      await preloadImages(imageUrls);
   }
   isImagesReady.value = true;
});
</script>

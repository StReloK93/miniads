<template>
   <section class="full-page flex flex-col">
      <aside class="grow relative">
         <main class="relative -mt-[calc(var(--safe-area-top)+var(--spacing)*4)]">
            <swiper :modules="[Pagination]" pagination class="h-64">
               <swiper-slide v-for="image in product?.images" :key="image.id">
                  <img :src="`/storage/${image.src}`" class="h-full w-full object-cover" />
               </swiper-slide>
            </swiper>
            <div
               class="absolute bottom-3 right-3 z-50 text-sm inline-flex items-center gap-1 px-2 py-0.5 z-bg-gradient backdrop-blur-sm border rounded-full border-(--z-color-border)"
               :class="{ 'flex-row-reverse': product?.price_type.position === 'left' }"
            >
               <span class="font-semibold">
                  {{ formatPrice(product?.price) }}
               </span>
               <span>
                  {{ product?.price_type.type }}
               </span>
            </div>
         </main>

         <main class="pt-6 px-3">
            <h1 class="leading-4 mb-4 text-xl">{{ product?.title }}</h1>
            <hr class="border-(--z-color-border) -mx-3" />
            <aside v-if="product?.parameter_values.length" class="flex gap-2 flex-wrap py-4 text-sm">
               <div
                  v-for="v in product.parameter_values"
                  :key="v.id"
                  class="flex gap-2 bg-(--z-bg-secondary) rounded-(--z-rounded) px-3 py-1"
               >
                  <span class="text-(--z-color-text-secondary)" v-if="!v.parameter.unit">
                     {{ v.parameter.title }}
                  </span>
                  <span class="font-semibold">
                     {{ v.value }}
                  </span>

                  <span v-if="v.parameter.unit" class="text-(--z-color-text-secondary)">
                     {{ v.parameter.unit }}
                  </span>
               </div>
            </aside>

            <hr class="border-(--z-color-border) -mx-3" />

            <div class="py-2 leading-5 text-sm">{{ product?.description }}</div>
         </main>
      </aside>
      <aside class="px-3 pt-3 border-t border-(--z-color-border) flex gap-3">
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
import { Heart, Phone } from "lucide-vue-next";
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

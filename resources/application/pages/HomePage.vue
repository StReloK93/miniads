<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']">
      <template #header>
         <BaseChangeDistrictModal @district-changed="onDistrictChanged" />
         <CategoriesSlider />
      </template>

      <template #content>
         <!-- <main class="mb-4 -mx-3">
            <swiper :slidesPerView="1.2" :space-between="20" class="w-full px-3!">
               <swiper-slide v-for="(card, index) in colorCards" :key="index">
                  <div class="h-32 p-4 border rounded-(--z-rounded) border-(--z-border) bg-(--z-card)">
                     <h3 class="mb-2 text-sm">{{ card.name }}</h3>
                     <p class="font-bold">{{ card.desc }}</p>
                  </div>
               </swiper-slide>
            </swiper>
         </main> -->
         <Transition mode="out-in">
            <main v-if="fullLoadingImages" class="flex flex-col gap-4">
               <BaseProductCard v-for="product in latest_ten" :product="product" :key="product.id" />
            </main>
            <main v-else class="flex flex-col gap-4">
               <BaseSkeletonCard v-for="n in 3" />
            </main>
         </Transition>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import BaseChangeDistrictModal from "@/components/BaseChangeDistrictModal.vue";
import BaseSkeletonCard from "@/components/BaseSkeletonCard.vue";
import CategoriesSlider from "@/components/CategoriesSlider.vue";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { preloadImages } from "@/modules/Helpers";
import BaseProductCard from "@/components/BaseProductCard.vue";
import { IProduct } from "@shared/types";
import { onMounted, ref } from "vue";

const { data: latest_ten, execute: executeLatest } = useFetchDecorator<IProduct[]>(ProductRepo.latestTen);

const fullLoadingImages = ref<boolean>(false);

function onDistrictChanged(districtId: number) {
   executeLatest();
}

onMounted(async () => {
   await executeLatest();

   const images = <any[]>[];
   latest_ten.value
      ?.filter((product) => product.images.length > 0)
      .forEach((product) => {
         const imageUrls = `/storage/${product.images[0].src}`;
         images.push(imageUrls);
      });

   await preloadImages(images);

   fullLoadingImages.value = true;
});
</script>

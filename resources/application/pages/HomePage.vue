<template>
   <NavigationPageDecorator>
      <template #header>
         <main v-if="AuthStore.user" class="flex items-center justify-between mb-4">
            <aside>
               <h2 class="mb-0.5 text-(--color-text-secondary)">{{ AuthStore.user?.name }}</h2>
               <div class="flex gap-2 items-center text-sm">
                  <MapPin class="size-3" />
                  Uchquduq
                  <ChevronDown class="size-4" />
               </div>
            </aside>
            <aside></aside>
         </main>
         <main class="border-b border-(--z-border) pb-4 -mx-4">
            <div class="flex items-center text-sm justify-between mb-2 px-4">
               <h3 class="text-slate-600">Bo'limlar</h3>
            </div>
            <swiper :slidesPerView="5.5" :space-between="10" :modules="[FreeMode]" class="w-full px-4!">
               <swiper-slide>
                  <RouterLink :to="{ name: 'categories' }" class="select-none inline-flex flex-col items-center gap-2">
                     <div
                        class="size-14 grid place-items-center bg-black text-white border border-(--z-border) rounded-full"
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

      <template #content>
         <main class="my-4 -mx-3">
            <swiper :slidesPerView="1.2" :space-between="20" class="w-full px-3!">
               <swiper-slide v-for="(card, index) in colorCards" :key="index">
                  <div class="h-32 p-4 border rounded-(--z-rounded) border-(--z-border) bg-(--z-card)">
                     <h3 class="mb-2 text-sm">{{ card.name }}</h3>
                     <p class="font-bold">{{ card.desc }}</p>
                  </div>
               </swiper-slide>
            </swiper>
         </main>
         <main class="flex flex-col gap-4">
            <TransitionGroup>
               <BaseProductCard
                  v-if="fullLoadingImages"
                  v-for="product in latest_ten"
                  :product="product"
                  :key="product.id"
               />
               <aside v-else v-for="value in 3" class="skeleton h-50"></aside>
            </TransitionGroup>
         </main>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { FreeMode } from "swiper/modules";
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { useCategory } from "@shared/entities/Category/useCategory";
import icons from "@/modules/icons";
import { ChevronDown, LayoutGrid, MapPin } from "lucide-vue-next";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { preloadImages } from "@/modules/Helpers";
import { useAuth } from "@shared/store/useAuth";
import BaseProductCard from "@/components/BaseProductCard.vue";
import { IProduct } from "@shared/types";
import { onMounted, ref } from "vue";
const AuthStore = useAuth();
const CategoryStore = useCategory();

const { data: latest_ten, execute: executeLatest } = useFetchDecorator<IProduct[]>(ProductRepo.latestTen);
const fullLoadingImages = ref<boolean>(false);
const colorCards = [
   {
      name: "Ocean Blue",
      desc: "Toza va ishonchli, umumiy e'lonlar uchun",
   },
   {
      name: "Warm Peach",
      desc: "Iliq va premium, uy-joy va mebel uchun",
   },
   {
      name: "Mint Green",
      desc: "Zamonaviy va yengil, xizmatlar uchun",
   },
];

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

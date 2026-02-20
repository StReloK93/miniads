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
         <main class="border-b border-(--z-color-border) pb-4 -mx-4">
            <div class="flex items-center text-sm justify-between mb-2 px-4">
               <h3 class="text-slate-600">Kategoriyalar</h3>
               <RouterLink :to="{ name: 'categories' }" class="text-(--z-color-primary)"> Barchasi </RouterLink>
            </div>
            <swiper :slidesPerView="5.5" :space-between="20" class="w-full px-4!">
               <swiper-slide v-for="category in CategoryStore.parentCategories" :key="category.id">
                  <RouterLink
                     :to="
                        category.is_page
                           ? { name: 'category', params: { id: category.id } }
                           : { name: 'categories', query: { category_id: category.id } }
                     "
                     class="select-none inline-flex flex-col items-center gap-1"
                  >
                     <div
                        class="size-14 grid place-items-center bg-(--z-bg-secondary) border border-(--z-color-border) rounded-(--z-rounded)"
                     >
                        <component
                           :is="icons[category.image]"
                           stroke-width="1.5"
                           class="size-5 text-(--z-color-primary)"
                        />
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
         <main class="mb-4 -mx-4">
            <swiper :slidesPerView="1.2" :space-between="20" class="w-full px-4!">
               <swiper-slide v-for="(card, index) in colorCards" :key="index">
                  <div class="h-32 p-4 border rounded-(--z-rounded) border-(--z-color-border) z-bg-gradient">
                     <h3 class="mb-2 text-sm">{{ card.name }}</h3>
                     <p class="font-black">{{ card.desc }}</p>
                  </div>
               </swiper-slide>
            </swiper>
         </main>
         <main class="flex flex-col gap-4">
            <BaseProductCard v-for="product in latest_ten" :product="product" :key="product.id" />
            <BaseProductCard v-for="product in latest_ten" :product="product" :key="product.id" />
         </main>
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import ProductRepo from "@shared/entities/Product/ProductRepo";
import { useCategory } from "@shared/entities/Category/useCategory";
import icons from "@/modules/icons";
import { ChevronDown, MapPin } from "lucide-vue-next";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useFetchDecorator } from "@shared/composables/useFetch";
// import { preloadImages } from "@/modules/Helpers";
import { useAuth } from "@shared/store/useAuth";
import BaseProductCard from "@/components/BaseProductCard.vue";
import { IProduct } from "@shared/types";
import { onMounted } from "vue";
const AuthStore = useAuth();
const CategoryStore = useCategory();

const { data: latest_ten, execute: executeLatest } = useFetchDecorator<IProduct[]>(ProductRepo.latestTen);

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

onMounted(() => {
   executeLatest();
});
</script>

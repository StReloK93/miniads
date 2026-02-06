<template>
   <section>
      <main class="flex items-center justify-between mb-2">
         <aside v-if="AuthStore.user">
            <h2 class="mb-0.5 text-(--color-text-secondary)">{{ AuthStore.user?.name }}</h2>
            <div class="flex gap-2 items-center text-sm">
               <MapPin class="size-3" />
               Uchquduq
               <ChevronDown class="size-4" />
            </div>
         </aside>
         <aside></aside>
         <aside>
            <BaseButton severity="secondary" rounded>
               <Bell class="size-5" stroke-width="1" />
            </BaseButton>
         </aside>
      </main>
      <main>
         <article>
            <FieldText placeholder="Qidiruv" name="search" />
         </article>
      </main>
      <main class="mt-4 -mx-4">
         <div class="flex items-center justify-between mb-2 px-4">
            <h3 class="text-base text-slate-600">Ruknlar</h3>
            <RouterLink :to="{ name: 'categories' }" class="text-sm text-blue-800"> Barchasi </RouterLink>
         </div>
         <swiper :slidesPerView="5.5" :space-between="10" class="w-full px-4!">
            <!-- <template v-if="isImagesReady"> -->
            <swiper-slide v-for="category in data?.categories" :key="category.name">
               <RouterLink
                  :to="{ name: 'categories', query: { open: `${category.id}` } }"
                  class="select-none inline-flex flex-col items-center gap-1"
               >
                  <div
                     class="w-14 h-14 flex items-center justify-center gap-2 bg-slate-50 border-slate-100 border rounded-md"
                  >
                     <!-- <img :src="`${category.image}`" class="md:w-16 w-10 dark:invert" /> -->
                     <House stroke-width="1.5" v-if="category.id == 1" class="size-5 text-blue-800" />
                     <MonitorSmartphone stroke-width="1.5" v-if="category.id == 12" class="size-5 text-blue-800" />
                     <Car stroke-width="1.5" v-if="category.id == 17" class="size-5 text-blue-800" />
                     <Shirt stroke-width="1.5" v-if="category.id == 23" class="size-5 text-blue-800" />
                     <BriefcaseBusiness stroke-width="1.5" v-if="category.id == 28" class="size-5 text-blue-800" />
                     <Wrench stroke-width="1.5" v-if="category.id == 33" class="size-5 text-blue-800" />
                     <Armchair stroke-width="1.5" v-if="category.id == 38" class="size-5 text-blue-800" />
                     <GraduationCap stroke-width="1.5" v-if="category.id == 43" class="size-5 text-blue-800" />
                     <Cat stroke-width="1.5" v-if="category.id == 47" class="size-5 text-blue-800" />
                     <Megaphone stroke-width="1.5" v-if="category.id == 52" class="size-5 text-blue-800" />
                     <Gift stroke-width="1.5" v-if="category.id == 55" class="size-5 text-blue-800" />
                     <BadgeQuestionMark stroke-width="1.5" v-if="category.id == 56" class="size-5 text-blue-800" />
                  </div>
                  <div class="text-xs text-center">
                     {{ category.name }}
                  </div>
               </RouterLink>
            </swiper-slide>
            <!-- </template> -->
         </swiper>
      </main>

      <main class="">
         <swiper :slidesPerView="1.2" :space-between="10" class="w-full">
            <swiper-slide v-for="card in colorCards" :key="card.color">
               <div :class="[card.color]" class="w-full h-32 rounded-md p-4 relative">
                  <h3 class="rounded-full mb-2 relative text-sm">#{{ card.name }}</h3>
                  <p class="font-black">{{ card.desc }}</p>
               </div>
            </swiper-slide>
         </swiper>
      </main>
   </section>
</template>

<script setup lang="ts">
import {
   Armchair,
   BadgeQuestionMark,
   Bell,
   BriefcaseBusiness,
   Car,
   Cat,
   ChevronDown,
   ChevronRight,
   Gift,
   GraduationCap,
   House,
   MapPin,
   Megaphone,
   MonitorSmartphone,
   Shirt,
   Wrench,
} from "lucide-vue-next";

import { Swiper, SwiperSlide } from "swiper/vue";
import CategoryRepo from "@shared/entities/Category/CategoryRepo";
import { onMounted } from "vue";
// import { preloadImages } from "@/modules/Helpers";
import { useFetchDecorator } from "@shared/api/useFetch";
import { ICategory } from "@shared/types";
import { useAuth } from "@shared/store/useAuth";
const { data, execute: fetchCategories } = useFetchDecorator<{ categories: ICategory[]; breadcrumbs: any[] }>(
   CategoryRepo.parents,
);

const colorCards = [
   {
      color: "bg-gradient-to-r from-zinc-100 to-slate-100",
      name: "Ocean Blue",
      desc: "Toza va ishonchli, umumiy e'lonlar uchun",
   },
   {
      color: "bg-gradient-to-r from-slate-200 to-zinc-200",
      name: "Warm Peach",
      desc: "Iliq va premium, uy-joy va mebel uchun",
   },
   {
      color: "bg-gradient-to-r from-stone-100  to-slate-100",
      name: "Mint Green",
      desc: "Zamonaviy va yengil, xizmatlar uchun",
   },
];

const AuthStore = useAuth();
// const isImagesReady = ref(false);
onMounted(async () => {
   fetchCategories();
   // if (parentCategories.value) {
   //    const imageUrls: string[] = parentCategories.value.map((category) => category.image);
   //    await preloadImages(imageUrls);
   //    isImagesReady.value = true;
   // }
});
</script>

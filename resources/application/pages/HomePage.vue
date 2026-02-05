<template>
   <section>
      <main>
         <h2 class="text-2xl font-semibold">Salom, Aziz!</h2>
         <p class="text-sm text-slate-600 mt-1">E'lon berish uchun kategoriyani tanlang</p>
      </main>
      <main>
         <article>
            <FieldText placeholder="Qidiruv" name="search" />
         </article>
      </main>
      <main>
         <div class="flex items-center justify-between mt-4 mb-2">
            <h3 class="text-base text-slate-600">Ruknlar</h3>
            <div>
               <RouterLink :to="{ name: 'categories' }" class="text-sm text-blue-600 font-medium">
                  Barchasi
               </RouterLink>
            </div>
         </div>
         <swiper :slidesPerView="5.5" :space-between="10" class="w-full">
            <!-- <template v-if="isImagesReady"> -->
            <swiper-slide v-for="category in parentCategories" :key="category.name">
               <RouterLink
                  :to="{ name: 'categories', query: { open: `${category.id}` } }"
                  class="select-none inline-flex flex-col items-center gap-1"
               >
                  <div
                     class="w-14 h-14 flex items-center justify-center gap-2 bg-slate-50 border-slate-100 border rounded-md"
                  >
                     <!-- <img :src="`${category.image}`" class="md:w-16 w-10 dark:invert" /> -->
                     <House stroke-width="1.5" v-if="category.id == 1" class="size-5 text-blue-600" />
                     <MonitorSmartphone stroke-width="1.5" v-if="category.id == 12" class="size-5 text-blue-600" />
                     <Car stroke-width="1.5" v-if="category.id == 17" class="size-5 text-blue-600" />
                     <Shirt stroke-width="1.5" v-if="category.id == 23" class="size-5 text-blue-600" />
                     <BriefcaseBusiness stroke-width="1.5" v-if="category.id == 28" class="size-5 text-blue-600" />
                     <Wrench stroke-width="1.5" v-if="category.id == 33" class="size-5 text-blue-600" />
                     <Armchair stroke-width="1.5" v-if="category.id == 38" class="size-5 text-blue-600" />
                     <GraduationCap stroke-width="1.5" v-if="category.id == 43" class="size-5 text-blue-600" />
                     <Cat stroke-width="1.5" v-if="category.id == 47" class="size-5 text-blue-600" />
                     <Megaphone stroke-width="1.5" v-if="category.id == 52" class="size-5 text-blue-600" />
                     <Gift stroke-width="1.5" v-if="category.id == 55" class="size-5 text-blue-600" />
                     <BadgeQuestionMark stroke-width="1.5" v-if="category.id == 56" class="size-5 text-blue-600" />
                  </div>
                  <div class="text-xs text-center">
                     {{ category.name }}
                  </div>
               </RouterLink>
            </swiper-slide>
            <!-- </template> -->
         </swiper>
      </main>
   </section>
</template>

<script setup lang="ts">
import {
   Armchair,
   BadgeQuestionMark,
   BriefcaseBusiness,
   Car,
   Cat,
   Gift,
   GraduationCap,
   House,
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
const { data: parentCategories, execute: fetchCategories } = useFetchDecorator<ICategory[]>(CategoryRepo.parents);

// const isImagesReady = ref(false);
onMounted(async () => {
   await fetchCategories();
   // if (parentCategories.value) {
   //    const imageUrls: string[] = parentCategories.value.map((category) => category.image);
   //    await preloadImages(imageUrls);
   //    isImagesReady.value = true;
   // }
});
</script>

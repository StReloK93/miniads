<template>
   <section class="grid grid-rows-[auto_1fr]">
      <aside class="pb-4 mb-4 border-b border-(--z-border) -mx-4 px-4">
         <h3 class="font-extrabold text-xl mb-1">E'lon joylash</h3>
         <p class="title text-xs inline-flex">1-qadam: Shaharni tanlang</p>
      </aside>
      <div class="relative">
         <main class="absolute inset-0 overflow-y-scroll pb-4 no-scrollbar">
            <ul class="divide-y divide-(--z-muted)">
               <li v-for="cat in districts" :key="cat.id">
                  <div
                     @click="() => selectDistrict(cat)"
                     class="flex items-center justify-between py-2 active:bg-(--z-card) rounded-md"
                  >
                     <span> {{ cat.name }} </span>
                     <ChevronRight class="size-4" />
                  </div>
               </li>
            </ul>
         </main>
      </div>
   </section>
</template>

<script setup lang="ts">
import { ChevronRight } from "lucide-vue-next";
import { useFetchDecorator } from "@shared/composables/useFetch";
import { useRouter } from "vue-router";
import { onMounted } from "vue";
import { IDistrict } from "@shared/types";
import DistrictRepo from "@shared/entities/District/DistrictRepo";

const { data: districts, execute: executeDistricts } = useFetchDecorator<IDistrict[]>(DistrictRepo.index);
const router = useRouter();

function selectDistrict(district: IDistrict) {
   var cityId: number | null = null;
   if (district.id !== 0) {
      cityId = district.id;
   }

   router.push({
      name: "create-select-category",
      params: { cityId: cityId },
   });
}

onMounted(() => {
   executeDistricts();
   import("@pages/Create/CreateProductPage.vue");
});
</script>

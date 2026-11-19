<template>
   <section class="grid grid-rows-[auto_1fr]">
      <aside class="pb-4 mb-4 border-b border-(--z-border) -mx-4 px-4">
         <div class="flex items-center gap-2 mb-1">
            <PageBackButton />
            <h3 class="font-extrabold text-xl">{{ $t("common.postAd") }}</h3>
         </div>
         <p class="title text-xs inline-flex">{{ $t("create.step", { n: 1 }) }}: {{ $t("district.choose") }}</p>
      </aside>
      <div class="relative">
         <main class="absolute inset-0 overflow-y-scroll pb-4 no-scrollbar">
            <ul class="divide-y divide-(--z-muted)">
               <li v-for="cat in cityStore.cities" :key="cat.id">
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
import PageBackButton from "@/layouts/PageBackButton.vue";
import { useRouter } from "vue-router";
import { onMounted } from "vue";
import { IDistrict } from "@shared/types";
import { useCity } from "@shared/entities/District/useCity";
const cityStore = useCity();

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
   import("@pages/Create/CreateProductPage.vue");
});
</script>

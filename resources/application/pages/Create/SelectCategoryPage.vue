<template>
   <section class="grid grid-rows-[auto_1fr]">
      <aside class="pb-4 mb-4 border-b border-(--z-border) -mx-4 px-4">
         <div class="flex gap-2 items-center justify-between">
            <h3 class="font-extrabold text-xl mb-1">E'lon joylash</h3>

            <span class="text-xs text-(--z-muted-text) inline-flex items-center underline">
               <MapPin class="inline-block size-3 mr-1" />
               {{ selectedCity }}
            </span>
         </div>
         <p class="title text-xs">2-qadam: E'lon turi</p>
      </aside>
      <CategorySelector
         v-if="CategoryStore.parentCategories"
         :activeCategoryId="activeCategoryId"
         :categories="CategoryStore.parentCategories"
         @select-category="
            $router.push({ name: 'create-product', params: { categoryId: $event.id, cityId: route.params.cityId } })
         "
      />
   </section>
</template>

<script setup lang="ts">
import { MapPin } from "lucide-vue-next";
import DistrictRepo from "@shared/entities/District/DistrictRepo";
import { IDistrict } from "@shared/types";

const { data: districts, execute: executeDistricts } = useFetchDecorator<IDistrict[]>(DistrictRepo.index);

import CategorySelector from "@components/CategorySelector.vue";
import { useCategory } from "@shared/entities/Category/useCategory";
import { useRoute } from "vue-router";
import { computed, onMounted } from "vue";
import { useFetchDecorator } from "@shared/composables/useFetch";

const route = useRoute();

const selectedCity = computed(() => {
   const cityId = route.params.cityId ? Number(route.params.cityId) : 0;

   const selectedDistrict = districts.value?.find((d) => d.id === cityId);
   return selectedDistrict ? selectedDistrict.name : null;
});

const activeCategoryId = computed(() => {
   return route.query.category_id ? Number(route.query.category_id) : null;
});
const CategoryStore = useCategory();

onMounted(() => {
   executeDistricts();
   import("@pages/Create/CreateProductPage.vue");
});
</script>

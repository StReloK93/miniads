<template>
   <section class="grid grid-rows-[auto_1fr] gap-4 w-full h-dvh">
      <BackPreviusPage title="Qanday e'lon bermoqchisiz?" @close="$router.push({ name: 'home' })" model />
      <aside>
         <div class="p-2 px-4 text-sm text-gray-700 mb-2 bg-slate-100 rounded flex items-start gap-2">
            <Info class="size-5 mt-1" />
            E'loningiz qaysi kategoriyaga tegishli ekanligini tanlang.
         </div>

         <CategorySelector
            v-if="CategoryStore.parentCategories"
            :activeCategoryId
            :categories="CategoryStore.parentCategories"
            @select-category="$router.push({ name: 'create-product', params: { categoryId: $event.id } })"
         />
      </aside>
   </section>
</template>

<script setup lang="ts">
import CategorySelector from "@components/CategorySelector.vue";
import BackPreviusPage from "@/components/BackPreviusPage.vue";
import { useCategory } from "@shared/entities/Category/useCategory";
import { Info } from "lucide-vue-next";
import { useRoute } from "vue-router";
import { computed, onMounted } from "vue";

const route = useRoute();

const activeCategoryId = computed(() => {
   return route.query.category_id ? Number(route.query.category_id) : null;
});
const CategoryStore = useCategory();

onMounted(() => {
   import("@pages/Create/CreateProductPage.vue");
});
</script>

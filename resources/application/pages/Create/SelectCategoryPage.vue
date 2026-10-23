<template>
   <section class="h-full flex flex-col min-h-0 overflow-hidden">
      <aside class="shrink-0 pb-3 mb-3 border-b border-(--z-border) -mx-4 px-4">
         <div class="flex gap-2 items-center justify-between">
            <h3 class="font-extrabold text-xl">E'lon joylash</h3>
            <span class="text-xs text-(--z-muted-text) font-medium">1 / 2</span>
         </div>

         <!-- Segmented progress bar (etaplar alohida line, orasi ochiq) -->
         <div class="mt-2.5 flex flex-col gap-1.5">
            <div class="grid grid-cols-2 gap-1.5 w-full">
               <div class="h-1 bg-(--z-primary) rounded-full"></div>
               <div class="h-1 bg-(--z-border) rounded-full"></div>
            </div>
            <p class="text-xs text-(--z-muted-text) font-medium">Kategoriya tanlang</p>
         </div>
      </aside>

      <CategorySelector
         v-if="CategoryStore.parentCategories"
         :activeCategoryId="activeCategoryId"
         :categories="CategoryStore.parentCategories"
         class="flex-1 min-h-0"
         @select-category="
            $router.push({ name: 'create-product', params: { categoryId: $event.id } })
         "
      />
   </section>
</template>

<script setup lang="ts">
import CategorySelector from "@components/CategorySelector.vue";
import { useCategory } from "@shared/entities/Category/useCategory";
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

<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']">
      <template #header>
         <aside class="mb-4">
            <div class="flex items-center justify-between mb-2">
               <h3 class="font-bold text-xl">{{ $t("categories.title") }}</h3>
            </div>
            <p class="text-sm text-(--z-muted-text)">{{ $t("categories.subtitle") }}</p>
         </aside>
      </template>

      <template #content>
         <CategorySelector
            class="h-full"
            v-if="CategoryStore?.parentCategories?.length"
            :activeCategoryId="activeCategoryId"
            :categories="CategoryStore?.parentCategories"
            :counts="CategoryStore.counts"
            @selectCategory="(category) => $router.push({ name: 'category', params: { id: category.id } })"
         />
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import NavigationPageDecorator from "@/layouts/NavigationPageDecorator.vue";
import { useRoute } from "vue-router";
import { useCategory } from "@shared/entities/Category/useCategory";
import CategorySelector from "@/features/category/CategorySelector.vue";
import { computed, onMounted } from "vue";

const CategoryStore = useCategory();

// Sonlar kelmasa ham sahifa ishlaydi — shunchaki "N ta bo'lim" ko'rinadi
onMounted(() => CategoryStore.getCounts().catch(() => {}));

const route = useRoute();

const activeCategoryId = computed(() => {
   return route.query.category_id ? Number(route.query.category_id) : null;
});
</script>

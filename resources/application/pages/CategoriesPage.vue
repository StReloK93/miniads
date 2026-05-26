<template>
   <NavigationPageDecorator :header-class="['border-b', 'border-(--z-border)']">
      <template #header>
         <aside class="mb-4">
            <h3 class="font-bold text-xl mb-4">Bo'limlar</h3>
            <p class="text-sm text-(--z-muted-text)">Nimani izlayapsiz? Mos kategoriyani tanlang</p>
         </aside>
      </template>

      <template #content>
         <CategorySelector
            class="h-full"
            v-if="CategoryStore?.parentCategories?.length"
            :activeCategoryId="activeCategoryId"
            :categories="CategoryStore?.parentCategories"
            @selectCategory="(category) => $router.push({ name: 'category', params: { id: category.id } })"
         />
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import { useRoute } from "vue-router";
import { useCategory } from "@shared/entities/Category/useCategory";
import CategorySelector from "@/components/CategorySelector.vue";
import { computed } from "vue";

const CategoryStore = useCategory();

const route = useRoute();

const activeCategoryId = computed(() => {
   return route.query.category_id ? Number(route.query.category_id) : null;
});
</script>

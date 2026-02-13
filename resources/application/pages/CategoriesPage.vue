<template>
   <section class="flex flex-col gap-4">
      <BackPreviusPage :title="pageTitle" />
      <CategorySelector
         v-if="CategoryStore?.parentCategories?.length"
         :activeCategoryId="activeCategoryId"
         :categories="CategoryStore?.parentCategories"
         @selectCategory="(category) => $router.push({ name: 'category', params: { id: category.id } })"
      />
   </section>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";

import { useCategory } from "@shared/entities/Category/useCategory";
import CategorySelector from "@/components/CategorySelector.vue";
import { computed } from "vue";
import BackPreviusPage from "@/components/BackPreviusPage.vue";
const CategoryStore = useCategory();

const route = useRoute();

const activeCategoryId = computed(() => {
   return route.query.category_id ? Number(route.query.category_id) : null;
});

const pageTitle = computed(() => {
   return (route.meta.title as string) || "MiniAds";
});
</script>

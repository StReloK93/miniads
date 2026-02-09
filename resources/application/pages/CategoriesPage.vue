<template>
   <section class="flex flex-col gap-4">
      <BackPreviusPage :title="pageTitle" />
      <CategorySelector
         v-if="CategoryStore?.parentCategories?.length"
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
const route = useRoute();
const CategoryStore = useCategory();

const pageTitle = computed(() => {
   return (route.meta.title as string) || "MiniAds";
});
</script>

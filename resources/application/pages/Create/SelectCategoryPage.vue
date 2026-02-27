<template>
   <section>
      <aside class="pb-4 mb-4 border-b border-(--z-border) -mx-4 px-4">
         <h3 class="font-extrabold text-xl mb-1">E'lon joylash</h3>
         <p class="title text-xs">1-qadam: E'lon turi</p>
      </aside>
      <CategorySelector
         v-if="CategoryStore.parentCategories"
         :activeCategoryId
         :categories="CategoryStore.parentCategories"
         @select-category="$router.push({ name: 'create-product', params: { categoryId: $event.id } })"
      />
   </section>
</template>

<script setup lang="ts">
import CategorySelector from "@components/CategorySelector.vue";
import { useCategory } from "@shared/entities/Category/useCategory";
import { Info, Slash } from "lucide-vue-next";
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

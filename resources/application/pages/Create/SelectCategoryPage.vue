<template>
   <section class="px-3">
      <div
         class="py-2 px-3 text-sm text-(--z-color-text-primary) mb-2 bg-(--z-color-primary)/5 rounded-md flex items-start gap-2"
      >
         <Info class="size-5 mt-1" />
         E'loningiz qaysi kategoriyaga tegishli ekanligini tanlang.
      </div>

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

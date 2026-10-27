<template>
   <!--
      Ixcham chiplar qatori: bo'limlar bir bosishda, lekin lentadan joy olmaydi.
      Barcha bo'limlar tugmasi qidiruv yonida (HomeSearchBar).
   -->
   <nav class="-mx-4 px-4 pb-3 flex gap-2 overflow-x-auto no-scrollbar" :aria-label="$t('categories.title')">
      <RouterLink
         v-for="category in CategoryStore.parentCategories"
         :key="category.id"
         :to="
            category.is_page
               ? { name: 'category', params: { id: category.id } }
               : { name: 'categories', query: { category_id: category.id } }
         "
         class="shrink-0 h-9 pl-2.5 pr-3.5 inline-flex items-center gap-1.5 rounded-full bg-(--z-card) border border-(--z-border) text-xs font-medium whitespace-nowrap select-none transition active:scale-95"
      >
         <component :is="icons[category.image]" stroke-width="1.75" class="size-4 text-(--z-muted-text)" />
         {{ category.name }}
      </RouterLink>
   </nav>
</template>

<script setup lang="ts">
import icons from "@/features/category/icons";
import { useCategory } from "@shared/entities/Category/useCategory";

const CategoryStore = useCategory();
</script>

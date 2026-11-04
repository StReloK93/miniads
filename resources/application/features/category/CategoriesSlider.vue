<template>
   <!--
      Ixcham chiplar qatori: bo'limlar bir bosishda, lekin lentadan joy olmaydi.
      Barcha bo'limlar tugmasi qidiruv yonida (HomeSearchBar). E'lonlar soni — chip burchagidagi doirachada.
      pt-2: doiracha chipdan tepaga chiqadi, skroll konteyneri uni kesib qo'ymasin.
   -->
   <nav class="-mx-4 px-4 pt-2 pb-3 flex gap-2.5 overflow-x-auto no-scrollbar" :aria-label="$t('categories.title')">
      <RouterLink
         v-for="category in CategoryStore.parentCategories"
         :key="category.id"
         :to="
            category.is_page
               ? { name: 'category', params: { id: category.id } }
               : { name: 'categories', query: { category_id: category.id } }
         "
         class="relative shrink-0 h-9 pl-2.5 pr-3.5 inline-flex items-center gap-1.5 rounded-full bg-(--z-card) border border-(--z-border) text-xs font-medium whitespace-nowrap select-none transition active:scale-95"
      >
         <component :is="icons[category.image]" stroke-width="1.75" class="size-4 text-(--z-muted-text)" />
         {{ category.name }}
         <CountBadge :count="CategoryStore.countFor(category.id)" class="-top-2 -right-1.5" />
      </RouterLink>
   </nav>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import icons from "@/features/category/icons";
import CountBadge from "@/features/category/CountBadge.vue";
import { useCategory } from "@shared/entities/Category/useCategory";

const CategoryStore = useCategory();

onMounted(() => {
   CategoryStore.getCounts().catch((error) => console.error("Category counts failed.", error));
});
</script>

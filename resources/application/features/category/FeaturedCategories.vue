<template>
   <!--
      Tezkor bo'limlar: har kuni kerak bo'ladigan bo'limlar (kafe, naqd-plastik, yo'lovchilar...).
      Qaysilari chiqishini admin belgilaydi. Faqat ikonka, nomi va tepasida e'lonlar soni.
   -->
   <nav v-if="categories.length" class="featured-row mb-4" :aria-label="$t('categories.featured')">
      <RouterLink
         v-for="(category, index) in categories"
         :key="category.id"
         :to="linkTo(category)"
         class="featured-item"
         :style="{ '--tone': tones[index % tones.length] }"
      >
         <span class="relative">
            <component :is="iconOf(category)" stroke-width="1.6" class="featured-icon" />
            <CountBadge :count="CategoryStore.countFor(category.id)" class="featured-count" />
         </span>
         <span class="featured-name">{{ category.name }}</span>
      </RouterLink>
   </nav>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { LayoutGrid } from "lucide-vue-next";
import icons from "@/features/category/icons";
import CountBadge from "@/features/category/CountBadge.vue";
import { useCategory } from "@shared/entities/Category/useCategory";
import type { ICategory } from "@shared/types";

const CategoryStore = useCategory();

const categories = computed(() => CategoryStore.featuredCategories ?? []);

/** Fon dog'lari bilan bir oila: moviy, binafsha, pushti, firuza (glass.scss → --z-ambient) */
const tones = ["59 130 246", "139 92 246", "236 72 153", "20 184 166", "245 158 11"];

function iconOf(category: ICategory) {
   return icons[category.image as keyof typeof icons] ?? LayoutGrid;
}

function linkTo(category: ICategory) {
   return category.is_page
      ? { name: "category", params: { id: category.id } }
      : { name: "categories", query: { category_id: category.id } };
}

onMounted(() => {
   CategoryStore.getFeaturedCategories().catch((error) => console.error("Featured categories failed.", error));
   CategoryStore.getCounts().catch((error) => console.error("Category counts failed.", error));
});
</script>

<style scoped lang="scss">
/* 4 ta teng ustun; ko'p bo'lsa — yon tomonga suriladi */
.featured-row {
   display: grid;
   grid-auto-flow: column;
   grid-auto-columns: calc((100% - 1.5rem) / 4);
   gap: 0.5rem;
   margin-inline: -1rem;
   padding: 0.375rem 1rem 0;
   overflow-x: auto;
   scrollbar-width: none;

   &::-webkit-scrollbar {
      display: none;
   }
}

.featured-item {
   display: flex;
   flex-direction: column;
   align-items: center;
   gap: 0.375rem;
   color: var(--z-foreground);
   user-select: none;
   -webkit-tap-highlight-color: transparent;
   transition: transform var(--duration) var(--ease-soft);

   &:active {
      transform: scale(0.92);
   }
}

.featured-icon {
   display: block;
   width: 1.875rem;
   height: 1.875rem;
   color: rgb(var(--tone));
}

/* E'lonlar soni (CountBadge) — ikonka tepasida, o'ng tomonda */
.featured-count {
   top: -0.5rem;
   left: calc(100% - 0.625rem);
}

.featured-name {
   display: -webkit-box;
   -webkit-box-orient: vertical;
   -webkit-line-clamp: 2;
   overflow: hidden;
   font-size: 0.6875rem;
   font-weight: 600;
   line-height: 1.2;
   text-align: center;
   overflow-wrap: break-word;
}

:global(html.dark) .featured-icon {
   filter: brightness(1.3);
}
</style>

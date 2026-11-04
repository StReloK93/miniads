import { defineStore } from "pinia";
import CategoryRepo from "./CategoryRepo";
import { ref } from "vue";
import type { ICategory } from "@shared/types";
export const useCategory = defineStore("useCategory", () => {
   const parentCategories = ref();
   /** Bosh sahifadagi "Tezkor bo'limlar"; bir marta yuklanadi (server 15 daqiqa keshlaydi) */
   const featuredCategories = ref<ICategory[] | null>(null);
   /** Kategoriyadagi faol e'lonlar soni. Server 5 daqiqa keshlaydi — bu yerda ham shuncha vaqt qayta so'ralmaydi */
   const counts = ref<Record<number, number> | null>(null);
   let countsFetchedAt = 0;
   const COUNTS_TTL = 5 * 60 * 1000;

   async function getParentCategories() {
      const { data: categories } = await CategoryRepo.parents();
      parentCategories.value = categories;
   }

   async function getFeaturedCategories() {
      if (featuredCategories.value) return;
      const { data } = await CategoryRepo.featured();
      featuredCategories.value = data;
   }

   async function getCounts() {
      if (counts.value && Date.now() - countsFetchedAt < COUNTS_TTL) return;
      const { data } = await CategoryRepo.counts();
      counts.value = data;
      countsFetchedAt = Date.now();
   }

   function findInTree(nodes: ICategory[] | undefined, id: number): ICategory | null {
      for (const node of nodes ?? []) {
         if (node.id === id) return node;
         const found = findInTree(node.children, id);
         if (found) return found;
      }
      return null;
   }

   function sumCounts(node: ICategory): number {
      const own = counts.value?.[node.id] ?? 0;
      return own + (node.children ?? []).reduce((total, child) => total + sumCounts(child), 0);
   }

   /** Kategoriyadagi faol e'lonlar soni — ichki bo'limlari ham qo'shib (sonlar hali kelmagan bo'lsa 0) */
   function countFor(categoryId: number): number {
      if (!counts.value) return 0;
      const node = findInTree(parentCategories.value, categoryId);
      return node ? sumCounts(node) : (counts.value[categoryId] ?? 0);
   }

   return {
      parentCategories,
      featuredCategories,
      counts,
      getParentCategories,
      getFeaturedCategories,
      getCounts,
      countFor,
   };
});

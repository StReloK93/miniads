import { defineStore } from "pinia";
import CategoryRepo from "./CategoryRepo";
import { ref } from "vue";
export const useCategory = defineStore("useCategory", () => {
   const parentCategories = ref();
   /** Kategoriyadagi faol e'lonlar soni. Server 5 daqiqa keshlaydi — bu yerda ham shuncha vaqt qayta so'ralmaydi */
   const counts = ref<Record<number, number> | null>(null);
   let countsFetchedAt = 0;
   const COUNTS_TTL = 5 * 60 * 1000;

   async function getParentCategories() {
      const { data: categories } = await CategoryRepo.parents();
      parentCategories.value = categories;
   }

   async function getCounts() {
      if (counts.value && Date.now() - countsFetchedAt < COUNTS_TTL) return;
      const { data } = await CategoryRepo.counts();
      counts.value = data;
      countsFetchedAt = Date.now();
   }

   return { parentCategories, counts, getParentCategories, getCounts };
});

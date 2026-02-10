import { defineStore } from "pinia";
import CategoryRepo from "./CategoryRepo";
import { ref } from "vue";
export const useCategory = defineStore("useCategory", () => {
   const parentCategories = ref();

   async function getParentCategories() {
      const { data: categories } = await CategoryRepo.parents();
      parentCategories.value = categories;
   }

   return { parentCategories, getParentCategories };
});

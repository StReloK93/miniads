import { defineStore } from "pinia";
import CategoryRepo from "./CategoryRepo";
import { ref } from "vue";
export const useCategory = defineStore("useCategory", () => {
   const parentCategories = ref();

   CategoryRepo.parents().then(({ data }) => {
      parentCategories.value = data.categories;
   });

   return { parentCategories };
});

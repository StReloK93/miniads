import { defineStore } from "pinia";
import CategoryRepo from "./CategoryRepo";
import { ref } from "vue";
export const useCategory = defineStore("useCategory", () => {
   const parentCategories = ref();

   async function getParentCategories() {
      const { data } = await CategoryRepo.parents();
      console.log(data);
      parentCategories.value = data.categories;
   }

   return { parentCategories, getParentCategories };
});

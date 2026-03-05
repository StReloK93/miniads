import { ref } from "vue";

const STORAGE_KEY = "recent_searches";

export function useRecentSearches() {
   const searches = ref<string[]>(JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]"));

   function addSearch(term: string) {
      term = term.trim();
      if (!term) return;

      searches.value = [term, ...searches.value.filter((s) => s !== term)].slice(0, 4);

      localStorage.setItem(STORAGE_KEY, JSON.stringify(searches.value));
   }

   function removeSearch(term: string) {
      searches.value = searches.value.filter((s) => s !== term);
      localStorage.setItem(STORAGE_KEY, JSON.stringify(searches.value));
   }

   function clear() {
      searches.value = [];
      localStorage.removeItem(STORAGE_KEY);
   }

   return { searches, addSearch, removeSearch, clear };
}

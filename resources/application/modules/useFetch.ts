import { ref } from "vue";
import axios from "axios";

const api = axios.create({
   baseURL: "/api",
   headers: { "Content-Type": "application/json" },
   withCredentials: false,
});

function useFetchDecorator<T>(fetchFn) {
   const data = ref<T | null>(null);
   const isLoading = ref(false);
   const isFirstLoading = ref(true); // Dastlab true bo'ladi
   const error = ref<any>(null);

   const execute = async (...args) => {
      isLoading.value = true;
      error.value = null;

      try {
         const response = await fetchFn(...args);
         data.value = response.data;
         return response;
      } catch (err) {
         error.value = err;
         throw err;
      } finally {
         isLoading.value = false;
         // Birinchi marta yuklanish tugagandan keyin har doim false bo'ladi
         isFirstLoading.value = false;
      }
   };

   return {
      data,
      isLoading,
      isFirstLoading,
      error,
      execute,
   };
}

export { useFetchDecorator, api };

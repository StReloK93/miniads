import { ref } from "vue";
import type { AxiosResponse } from "axios";
import axios from "axios";
type HttpMethod = "get" | "post" | "put" | "delete"; // Define allowed HTTP methods

const api = axios.create({
   baseURL: "/api",
   headers: { "Content-Type": "application/json" },
   withCredentials: false,
});
type FetchOptions<T = unknown> = {
   url: string;
   formData?: object;
   method?: HttpMethod;
   onLoad?: (result: { data: T; error: object | null }) => void;
   options?: object;
};

function useFetch<T>({ url, formData = {}, method = "get", onLoad, options }: FetchOptions<T>) {
   const data = ref<T | null>(null);
   const error = ref<object | null>(null);
   const isLoading = ref(true);
   const isFirstLoading = ref(true);

   const fetchData = async (formData?: object, options?: object) => {
      isLoading.value = true;
      error.value = null;

      try {
         const response: AxiosResponse<T> = await api[method](url, formData, options); // No need to lowercase, method is already typed
         data.value = response.data;
      } catch (err) {
         error.value = err as object | null;
      } finally {
         isLoading.value = false;
         isFirstLoading.value = false;
         if (onLoad) onLoad({ data: data.value as T, error: error.value });
      }
   };

   fetchData(formData, options);
   return { data, error, isLoading, fetchData, isFirstLoading };
}

export { useFetch, api };

export function useFetchDecorator<T>(fetchFn) {
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

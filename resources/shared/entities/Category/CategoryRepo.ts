import { api } from "@shared/api/useFetch";
import { ICategory } from "@shared/types";
import { ref } from "vue";
const baseURL = "categories";

const headerMultipart = {
   headers: {
      "Content-Type": "multipart/form-data",
   },
};

export default {
   index() {
      return api.get<ICategory[]>(`${baseURL}`);
   },
   async store(parent_id: number | string | null, formData: { name: string; file?: File }) {
      const loading = ref(true);
      await api.post(`${baseURL}`, { parent_id, ...formData }, headerMultipart).finally(() => {
         loading.value = false;
      });
      return { loading };
   },
   parents(parent_id = null) {
      if (!parent_id) return api.get<ICategory[]>(`${baseURL}/parents`);
      return api.get<ICategory[]>(`${baseURL}/parents/${parent_id}`);
   },
   products(categoryId: string) {
      return api.get<ICategory>(`${baseURL}/${categoryId}/products`);
   },
   update(id: string, formData: { name: string; file?: File }) {
      return api.post(`${baseURL}/${id}`, formData, headerMultipart);
   },
   show(id: number | string) {
      return api.get<ICategory>(`${baseURL}/${id}`);
   },
   changeParent(id: number, parent_id: number | string | null) {
      return api.post(`${baseURL}/change_parent/${id}`, { parent_id });
   },
};

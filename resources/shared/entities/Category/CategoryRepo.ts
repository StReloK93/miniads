import { api } from "@shared/composables/useFetch";
import { ICategory } from "@shared/types";
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
   store(parent_id: number | string | null, formData: { name: string; image?: File | string }) {
      return api.post<ICategory>(`${baseURL}`, { parent_id, ...formData }, headerMultipart);
   },
   async parents() {
      return await api.get<ICategory[]>(`${baseURL}/parents`);
   },
   /** Har bir kategoriyadagi faol e'lonlar soni: { [category_id]: soni } */
   counts() {
      return api.get<Record<number, number>>(`${baseURL}/counts`);
   },
   products(categoryId: string) {
      return api.get<ICategory>(`${baseURL}/${categoryId}/products`);
   },
   update(id: string, formData: { name: string; image?: File | string }) {
      return api.post(`${baseURL}/${id}`, formData, headerMultipart);
   },
   show(id: number | string) {
      return api.get<ICategory>(`${baseURL}/${id}`);
   },
   changeParent(id: number, parent_id: number | string | null) {
      return api.post(`${baseURL}/change_parent/${id}`, { parent_id });
   },
   delete(id: number | string) {
      return api.delete(`${baseURL}/${id}`);
   },
};

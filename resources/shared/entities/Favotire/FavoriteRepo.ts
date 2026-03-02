import { api } from "@shared/composables/useFetch";
import { IProduct } from "@shared/types";
const baseURL = "favorites";

export default {
   index() {
      return api.get<IProduct>(`${baseURL}`);
   },
   store(product_id: number) {
      return api.post<IProduct>(`${baseURL}`, { product_id });
   },
   delete(product_id: number) {
      return api.delete(`${baseURL}/${product_id}`);
   },
};

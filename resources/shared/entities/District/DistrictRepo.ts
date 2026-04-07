import { api } from "@shared/composables/useFetch";
import { IDistrict } from "@shared/types";
const baseURL = "districts";

export default {
   index() {
      return api.get<IDistrict[]>(`${baseURL}`);
   },
   // store(product_id: number) {
   //    return api.post<IProduct>(`${baseURL}`, { product_id });
   // },
   // delete(product_id: number) {
   //    return api.delete(`${baseURL}/${product_id}`);
   // },
};

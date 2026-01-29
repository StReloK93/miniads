import { api } from "@/modules/useFetch";
import { IProduct } from "@/types";
const baseURL = "products";

export default {
   index() {
      return api.get<IProduct[]>(`${baseURL}`);
   },
   store(formData: IProduct) {
      return api.post<IProduct>(`${baseURL}`, formData, {
         headers: {
            "Content-Type": "multipart/form-data",
         },
      });
   },
   show(id: string | number) {
      return api.get<IProduct[]>(`${baseURL}/${id}`);
   },
   update(id: string | number, formData: IProduct) {
      return api.put<IProduct>(`${baseURL}/${id}`, formData);
   },
};

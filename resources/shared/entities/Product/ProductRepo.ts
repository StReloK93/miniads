import { api } from "@shared/composables/useFetch";
import { IProduct } from "@shared/types";
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
      return api.get<IProduct>(`${baseURL}/${id}`);
   },
   update(id: string | number, formData: IProduct) {
      return api.post<IProduct>(`${baseURL}/${id}`, formData, {
         headers: {
            "Content-Type": "multipart/form-data",
         },
      });
   },
   latestTen() {
      return api.get<IProduct[]>(`${baseURL}/custom/latest_ten`);
   },

   deActivate(id: string | number) {
      return api.post(`${baseURL}/${id}/deactivate`, {});
   },

   myAds(status: string) {
      return api.get<IProduct[]>(`${baseURL}/custom/my_ads/${status}`);
   },

   search(search: string) {
      return api.get<IProduct[]>(`${baseURL}/custom/search?search=${search}`);
   },

   delete(id: string | number) {
      return api.delete(`${baseURL}/${id}`);
   },
};

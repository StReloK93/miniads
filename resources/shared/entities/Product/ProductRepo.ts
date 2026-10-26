import { api } from "@shared/composables/useFetch";
import { IPaginatedResponse, IProduct, IProductPayload } from "@shared/types";
const baseURL = "products";

export default {
   index() {
      return api.get<IProduct[]>(`${baseURL}`);
   },
   store(formData: IProductPayload) {
      return api.post<{ message: string; id: number }>(`${baseURL}`, formData, {
         headers: {
            "Content-Type": "multipart/form-data",
         },
      });
   },
   show(id: string | number) {
      return api.get<IProduct>(`${baseURL}/${id}`);
   },
   edit(id: string | number) {
      return api.get<IProduct>(`${baseURL}/${id}/edit`);
   },
   update(id: string | number, formData: IProductPayload) {
      return api.post<IProduct>(`${baseURL}/${id}`, formData, {
         headers: {
            "Content-Type": "multipart/form-data",
         },
      });
   },
   feed(page: number = 1, perPage: number = 12) {
      return api.get<IPaginatedResponse<IProduct>>(`${baseURL}/custom/feed`, {
         params: { page, per_page: perPage },
      });
   },
   latestTen() {
      return api.get<IProduct[]>(`${baseURL}/custom/latest_ten`);
   },

   deactivate(id: string | number) {
      return api.post(`${baseURL}/${id}/deactivate`, {});
   },

   activate(id: string | number) {
      return api.post(`${baseURL}/${id}/activate`, {});
   },

   myAds(status: string) {
      return api.get<IProduct[]>(`${baseURL}/custom/my_ads/${status}`);
   },

   search(params: {
      search: string;
      city_id?: number | string;
      category_id?: number | string;
      price_from?: number | string;
      price_to?: number | string;
   }) {
      return api.get<IProduct[]>(`${baseURL}/custom/search`, {
         params,
      });
   },

   delete(id: string | number) {
      return api.delete(`${baseURL}/${id}`);
   },
};

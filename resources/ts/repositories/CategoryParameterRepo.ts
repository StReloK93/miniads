import { api } from "@/modules/useFetch";
import { ICategoryParameter } from "@/types";
const baseURL = "categories";

export default {
   index(categoryId: number | string) {
      return api.get<ICategoryParameter[]>(`${baseURL}/${categoryId}/parameters`);
   },

   store(categoryId: number | string, data: any) {
      return api.post<ICategoryParameter>(`${baseURL}/${categoryId}/parameters`, data);
   },

   update(categoryId: number, parameterId: number, data: any) {
      return api.patch(`${baseURL}/${categoryId}/parameters/${parameterId}`, data);
   },

   delete(categoryId: number, parameterId: number) {
      return api.delete(`${baseURL}/${categoryId}/parameters/${parameterId}`);
   },
};

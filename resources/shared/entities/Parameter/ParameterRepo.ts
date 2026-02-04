import { api } from "@shared/api/useFetch";
import { IParameter } from "@shared/types";
const baseURL = "parameters";

export default {
   index() {
      return api.get<IParameter[]>(`${baseURL}`);
   },
   store(formData: IParameter) {
      return api.post<IParameter>(`${baseURL}`, formData);
   },
   show(id: string | number) {
      return api.get<IParameter[]>(`${baseURL}/${id}`);
   },
   update(id: string | number, formData: IParameter) {
      return api.put<IParameter>(`${baseURL}/${id}`, formData);
   },
};

import { api } from "@shared/composables/useFetch";
import type { IVitrina, IVitrinaItem, IVitrinaSection, VitrinaType } from "@shared/types";

const multipart = { headers: { "Content-Type": "multipart/form-data" } };

export default {
   /** Katalog: tasdiqlanganlar; cityId — shu shahar va butun viloyat, type — turi bo'yicha */
   index(params?: { city_id?: number | null; type?: VitrinaType | null }) {
      const query = Object.fromEntries(Object.entries(params ?? {}).filter(([, value]) => value));
      return api.get<IVitrina[]>("vitrinas", { params: query });
   },
   show(slug: string | number) {
      return api.get<IVitrina>(`vitrinas/${slug}`);
   },

   /* ----- O'z vitrinam ----- */
   mine() {
      return api.get<{ vitrina: IVitrina | null }>("my-vitrina");
   },
   save(form: FormData) {
      return api.post<IVitrina>("my-vitrina", form, multipart);
   },
   storeSection(name: string) {
      return api.post<IVitrinaSection>("my-vitrina/sections", { name });
   },
   updateSection(id: number, name: string) {
      return api.patch<IVitrinaSection>(`my-vitrina/sections/${id}`, { name });
   },
   deleteSection(id: number) {
      return api.delete(`my-vitrina/sections/${id}`);
   },
   /** id bo'lsa — tahrirlash (POST + multipart: fayllar PUT'da o'qilmaydi) */
   saveItem(form: FormData, id?: number) {
      return api.post<IVitrinaItem>(id ? `my-vitrina/items/${id}` : "my-vitrina/items", form, multipart);
   },
   deleteItem(id: number) {
      return api.delete(`my-vitrina/items/${id}`);
   },
};

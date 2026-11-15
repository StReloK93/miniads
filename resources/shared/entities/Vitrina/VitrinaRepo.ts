import { api } from "@shared/composables/useFetch";
import type { IVitrina, IVitrinaItem, IVitrinaSection, IVitrinaStats, VitrinaType } from "@shared/types";

const multipart = { headers: { "Content-Type": "multipart/form-data" } };

export default {
   /**
    * Tasdiqlanganlar; city_id — shu shahar va butun viloyat, type — turi bo'yicha.
    * strip — bosh sahifa qatori: 12 ta, kunlik aralashtirilgan
    */
   index(params?: { city_id?: number | null; type?: VitrinaType | null; strip?: boolean }) {
      const query = Object.fromEntries(
         Object.entries(params ?? {})
            .filter(([, value]) => value)
            .map(([key, value]) => [key, value === true ? 1 : value]),
      );
      return api.get<IVitrina[]>("vitrinas", { params: query });
   },
   show(slug: string | number) {
      return api.get<IVitrina>(`vitrinas/${slug}`);
   },
   /** Qidiruvdagi "Vitrinalardan" bloki; city_id — shu shahar va butun viloyat */
   searchItems(search: string, cityId?: number | null) {
      return api.get<IVitrinaItem[]>("vitrina-items/search", {
         params: cityId ? { search, city_id: cityId } : { search },
      });
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
   /** Mahsulotlar tartibi: barcha id'lar yangi tartibda */
   orderItems(ids: number[]) {
      return api.post<{ id: number; position: number }[]>("my-vitrina/items/order", { ids });
   },
   stats() {
      return api.get<IVitrinaStats>("my-vitrina/stats");
   },
};

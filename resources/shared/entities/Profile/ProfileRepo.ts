import { api } from "@shared/composables/useFetch";
import { ISavedSearch, IUser } from "@shared/types";

export interface IProfileSummary {
   stats: { active: number; total: number; views: number; favorites: number };
   saved_searches: ISavedSearch[];
   support_username: string | null;
}

export default {
   show() {
      return api.get<IProfileSummary>("profile");
   },

   update(values: Partial<Pick<IUser, "phone" | "notify_saved_searches" | "notify_expiry">>) {
      return api.patch<IUser>("profile", values);
   },

   writeAccess(allowed: boolean) {
      return api.post<IUser>("profile/write-access", { allowed });
   },

   /** Admin statistikasi uchun: qidiruv "o'rnashgan"da bir marta */
   logSearch(query: string, resultsCount: number) {
      return api.post("search-log", { query, results_count: resultsCount });
   },

   savedSearches() {
      return api.get<ISavedSearch[]>("saved-searches");
   },

   savedSearch(id: number | string) {
      return api.get<ISavedSearch>(`saved-searches/${id}`);
   },

   createSavedSearch(payload: Pick<ISavedSearch, "query" | "label" | "filters" | "params">) {
      return api.post<ISavedSearch>("saved-searches", payload);
   },

   updateSavedSearch(id: number, isPaused: boolean) {
      return api.patch<ISavedSearch>(`saved-searches/${id}`, { is_paused: isPaused });
   },

   deleteSavedSearch(id: number) {
      return api.delete(`saved-searches/${id}`);
   },
};

import { api } from "@shared/composables/useFetch";

export default {
   dashboard() {
      return api.get("admin/dashboard");
   },
   products(params?: Record<string, any>) {
      return api.get("admin/products", { params });
   },
   updateProductStatus(id: number, status: "active" | "inactive") {
      return api.patch(`admin/products/${id}/status`, { status });
   },
   deleteProduct(id: number) {
      return api.delete(`admin/products/${id}`);
   },
   restoreProduct(id: number) {
      return api.post(`admin/products/${id}/restore`);
   },
   forceDeleteProduct(id: number) {
      return api.delete(`admin/products/${id}/force`);
   },
   resolveReports(id: number) {
      return api.post(`admin/products/${id}/reports/resolve`);
   },
   bulkProducts(ids: number[], action: "archive" | "restore" | "activate" | "deactivate" | "resolve") {
      return api.post<{ message: string }>("admin/products/bulk", { ids, action });
   },
   searchStats(days: number) {
      return api.get("admin/search-stats", { params: { days } });
   },
   moderation() {
      return api.get<{ banned_words: string }>("admin/moderation");
   },
   updateModeration(bannedWords: string) {
      return api.post<{ message: string; count: number }>("admin/moderation", { banned_words: bannedWords });
   },
   users(params?: Record<string, any>) {
      return api.get("admin/users", { params });
   },
   updateUserRole(id: number, role: "admin" | "user") {
      return api.patch(`admin/users/${id}/role`, { role });
   },
   blockUser(id: number, archiveProducts: boolean) {
      return api.post<{ message: string }>(`admin/users/${id}/block`, { archive_products: archiveProducts });
   },
   unblockUser(id: number) {
      return api.post<{ message: string }>(`admin/users/${id}/unblock`);
   },
   channelPostDefaults() {
      return api.get<{ channels: string[]; button_text: string; button_url: string }>("admin/channel-post");
   },
   sendChannelPost(payload: { text: string; photo: File | null; button_text: string | null; button_url: string | null }) {
      return api.post<{ message: string }>("admin/channel-post", payload, {
         headers: { "Content-Type": "multipart/form-data" },
      });
   },
   reference(kind: "districts" | "price-types") {
      return api.get(kind);
   },
   storeReference(kind: "districts" | "price-types", values: Record<string, any>) {
      return api.post(kind, values);
   },
   updateReference(kind: "districts" | "price-types", id: number, values: Record<string, any>) {
      return api.put(`${kind}/${id}`, values);
   },
   deleteReference(kind: "districts" | "price-types", id: number) {
      return api.delete(`${kind}/${id}`);
   },
};

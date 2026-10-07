import { api } from "@shared/composables/useFetch";

export default {
   dashboard() {
      return api.get("admin/dashboard");
   },
   products(params: Record<string, string | number>) {
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
   users(params: Record<string, string | number>) {
      return api.get("admin/users", { params });
   },
   updateUserRole(id: number, role: "admin" | "user") {
      return api.patch(`admin/users/${id}/role`, { role });
   },
   reference(kind: "districts" | "price-types") {
      return api.get(kind);
   },
   storeReference(kind: "districts" | "price-types", values: Record<string, string>) {
      return api.post(kind, values);
   },
   updateReference(kind: "districts" | "price-types", id: number, values: Record<string, string>) {
      return api.put(`${kind}/${id}`, values);
   },
   deleteReference(kind: "districts" | "price-types", id: number) {
      return api.delete(`${kind}/${id}`);
   },
};

import { formatCategories } from "@/modules/Formatters";
import { useFetch, api } from "@/modules/useFetch";
import { ICategory } from "@/types";
import { TreeNode } from "primevue/treenode";
import { ref, watch } from "vue";
const baseURL = "categories";

const headerMultipart = {
   headers: {
      "Content-Type": "multipart/form-data",
   },
};

export default {
   index() {
      return api.get<ICategory[]>(`${baseURL}`);
   },
   async store(parent_id: number | string | null, formData: { name: string; file?: File }) {
      const loading = ref(true);
      await api.post(`${baseURL}`, { parent_id, ...formData }, headerMultipart).finally(() => {
         loading.value = false;
      });
      return { loading };
   },
   parents() {
      const { data, isFirstLoading, error, isLoading, fetchData } = useFetch<ICategory[]>({
         url: `${baseURL}/parents`,
      });

      const convertTreeNode = ref<TreeNode[]>([]);
      watch(
         () => data.value,
         () => {
            convertTreeNode.value = formatCategories(data.value || []);
         },
      );

      return { data, isFirstLoading, error, isLoading, fetchData, convertTreeNode };
   },
   products(categoryId: string) {
      return api.get<ICategory>(`${baseURL}/${categoryId}/products`);
   },
   update(id: string, formData: { name: string; file?: File }) {
      return api.post(`${baseURL}/${id}`, formData, headerMultipart);
   },
   show(id: string) {
      return api.get<ICategory>(`${baseURL}/${id}`);
   },
   changeParent(id: number, parent_id: number | string | null) {
      return api.post(`${baseURL}/change_parent/${id}`, { parent_id });
   },
};

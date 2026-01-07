import { formatCategories } from "@/modules/Formatters";
import { useFetch, api } from "@/modules/useFetch";
import { ICategory } from "@/types";
import { TreeNode } from "primevue/treenode";
import { ref, watch } from "vue";
const baseURL = "categories";

function index() {
   return useFetch<ICategory[]>({ url: `${baseURL}` });
}

async function store(parent_id: number | null, formData: { name: string; file?: File }) {
   const loading = ref(true);
   await api
      .post(
         `${baseURL}`,
         { parent_id, ...formData },
         {
            headers: { "Content-Type": "multipart/form-data" },
         },
      )
      .finally(() => {
         loading.value = false;
      });
   return { loading };
}

function parents() {
   const { data, isFirstLoading, error, isLoading, fetchData } = useFetch<ICategory[]>({
      url: `${baseURL}/parents`,
   });

   const convertTreeNode = ref<TreeNode[]>([]);
   watch(
      () => data.value,
      () => (convertTreeNode.value = formatCategories(data.value || [])),
   );

   return { data, isFirstLoading, error, isLoading, fetchData, convertTreeNode };
}

async function update(id: string, formData: { name: string; file?: File }) {
   const loading = ref(true);
   await api
      .post(`${baseURL}/${id}`, formData, {
         headers: { "Content-Type": "multipart/form-data" },
      })
      .finally(() => {
         loading.value = false;
      });
   return { loading };
}

async function changeParent(id: number, parent_id: number | string | null) {
   const loading = ref(true);
   await api.post(`${baseURL}/change_parent/${id}`, { parent_id }).finally(() => {
      loading.value = false;
   });
   return { loading };
}

function show(id: string, onLoad?: (result: { data: ICategory; error: object | null }) => void) {
   return useFetch<ICategory>({ url: `${baseURL}/${id}`, onLoad });
}

export default { index, store, parents, update, show, changeParent };

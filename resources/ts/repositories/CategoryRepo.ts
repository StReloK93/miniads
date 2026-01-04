import { useFetch, api } from "@/modules/useFetch";
import { ICategory } from "@/types";
import { ref } from "vue";
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
   return useFetch<ICategory[]>({ url: `${baseURL}/parents` });
}

function update(id: string, formData: { name: string; file?: File }) {
   return useFetch<ICategory>({
      url: `${baseURL}/${id}`,
      method: "put",
      formData: formData,
      options: {
         headers: { "Content-Type": "multipart/form-data" },
      },
   });
}

function show(id: string, onLoad?: (result: { data: ICategory; error: object | null }) => void) {
   return useFetch<ICategory>({ url: `${baseURL}/${id}`, onLoad });
}

export default { index, store, parents, update, show };

import { useFetch } from "@/modules/useFetch";
import { ICategory } from "@/types";
const baseURL = "categories";

function index() {
   return useFetch<ICategory[]>({ url: `${baseURL}` });
}

function store(parent_id: number | null, name: string) {
   return useFetch<ICategory[]>({
      url: `${baseURL}`,
      method: "post",
      formData: { parent_id, name },
   });
}

export default { index, store };

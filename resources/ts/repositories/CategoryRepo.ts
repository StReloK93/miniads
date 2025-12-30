import { useFetch, api } from "@/modules/useFetch";
import { ICategory } from "@/types";
const baseURL = "categories";

function index() {
   return useFetch<ICategory[]>({ url: `${baseURL}` });
}

function store(parent_id: number | null, formData: { name: string; file?: File }) {
   api.post(
      `${baseURL}`,
      { parent_id, ...formData },
      {
         headers: { "Content-Type": "multipart/form-data" },
      },
   );
   // return useFetch<ICategory[]>({
   //    url: `${baseURL}`,
   //    method: "post",
   //    formData: { parent_id, ...formData },
   //    headers: { "Content-Type": "multipart/form-data" },
   // });
}

export default { index, store };

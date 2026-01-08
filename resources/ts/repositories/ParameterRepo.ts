import { useFetch } from "@/modules/useFetch";
import { IParameter } from "@/types";
const baseURL = "parameters";

function index() {
   return useFetch<IParameter[]>({ url: `${baseURL}` });
}

function store(formData: IParameter) {
   return useFetch<IParameter[]>({ url: `${baseURL}`, method: "post", formData });
}

function show(
   id: string | number,
   onLoad?: (result: { data: IParameter; error: object | null }) => void,
) {
   return useFetch<IParameter>({ url: `${baseURL}/${id}`, onLoad });
}

function update(id: string | number, formData: IParameter) {
   return useFetch<IParameter>({ url: `${baseURL}/${id}`, method: "put", formData });
}

export default { index, store, show, update };

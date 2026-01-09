import { useFetch, api } from "@/modules/useFetch";
import { IParameter } from "@/types";
const baseURL = "parameters";

async function index() {
   return await api.get<IParameter[]>(`${baseURL}`);
}

async function store(formData: IParameter) {
   return await api.post<IParameter>(`${baseURL}`, formData);
}

function show(
   id: string | number,
   onLoad?: (result: { data: IParameter; error: object | null }) => void,
) {
   return useFetch<IParameter>({ url: `${baseURL}/${id}`, onLoad });
}

async function update(id: string | number, formData: IParameter) {
   return await api.put<IParameter>(`${baseURL}/${id}`, formData);
}

export default { index, store, show, update };

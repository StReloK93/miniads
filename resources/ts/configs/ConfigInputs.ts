import { PrimeVueInputs } from "@/configs/PrimeVueInputs";
import { api } from "@/modules/useFetch";
import { InputConfig } from "@/types";
import z from "zod";

const globalProps = { size: "small", fluid: true };
export const categoryInputs: InputConfig[] = [
   {
      component: PrimeVueInputs["InputText"],
      name: "name",
      placeholder: "Nomi",
      props: globalProps,
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },

   {
      component: PrimeVueInputs["CustomUpload"],
      name: "image",
      props: globalProps,
      schema: z.instanceof(File, { message: "Majburiy maydon!" }),
      class: ["mb-4"],
   },
];

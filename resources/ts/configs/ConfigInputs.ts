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
      schema: z
         .union([
            z.instanceof(File, { message: "Fayl bo'lishi shart" }),
            z.string().min(1, { message: "Yozuv bo'sh bo'lmasligi kerak" }),
         ])
         .optional()
         .nullable(),
      class: ["mb-4"],
   },
];

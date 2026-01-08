import { PrimeVueInputs } from "@/configs/PrimeVueInputs";
import { api } from "@/modules/useFetch";
import { InputConfig } from "@/types";
import z from "zod";

const globalProps = { size: "small", fluid: true };
export const categoryInputs: InputConfig[] = [
   {
      component: PrimeVueInputs["InputText"],
      name: "placeholder",
      placeholder: "Nomi",
      props: globalProps,
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["InputText"],
      name: "unit",
      placeholder: "O'lchov birligi",
      props: globalProps,
      schema: z.string({ message: "Majburiy maydon!" }).optional(),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["Select"],
      name: "type",
      placeholder: "Input turi",
      props: { ...globalProps, options: Object.keys(PrimeVueInputs) },
      schema: z.string({ message: "Majburiy maydon!" }),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["AutoComplete"],
      name: "options",
      placeholder: "Variantlar",
      props: { ...globalProps, multiple: true, typeahead: false },
      schema: z.array(z.string()).optional(),
      class: ["mb-4"],
   },
];

import { PrimeVueInputs } from "@/configs/PrimeVueInputs";
import { InputConfig } from "@/types";
import z from "zod";

export const categoryInputs: InputConfig[] = [
   {
      component: PrimeVueInputs["InputText"],
      props: { name: "username", placeholder: "Username", fluid: true },
      schema: z.string().min(3).max(20),
   },
   {
      component: PrimeVueInputs["InputNumber"],
      props: { name: "age", placeholder: "Age", fluid: true },
      schema: z.string().min(3).max(20),
   },
   {
      component: PrimeVueInputs["Select"],
      props: { name: "tags", placeholder: "Tags", fluid: true, options: ["lolo", "lala", "pepe"] },
      schema: z.string().min(3).max(20),
   },
];

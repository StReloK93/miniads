import { PrimeVueInputs } from "@/configs/PrimeVueInputs";
import { api } from "@/modules/useFetch";
import { InputConfig } from "@/types";
import z from "zod";

const globalProps = { size: "small", fluid: true };
export const categoryInputs: InputConfig[] = [
   {
      component: PrimeVueInputs["InputText"],
      name: "name",
      props: { placeholder: "Nomi", ...globalProps },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["Select"],
      name: "parent_id",
      props: null,
      generateProps: async function () {
         const { data: categories } = await api.get("/categories");
         this.props = {
            placeholder: "Kategoriya turi",
            disabled: true,
            options: categories,
            optionLabel: "name",
            optionValue: "id",
            ...globalProps,
         };
      },
      schema: z.number().nullable().optional(),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["CustomUpload"],
      name: "file",
      props: {
         placeholder: "file",
         ...globalProps,
      },
      schema: z.instanceof(File).nullable().optional(),
      class: ["mb-4"],
   },
];

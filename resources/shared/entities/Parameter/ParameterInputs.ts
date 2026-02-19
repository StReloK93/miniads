import { PrimeVueInputs } from "@admin/modules/PrimeVueInputs";
import { Inputs } from "@/modules/Inputs";
import { InputConfig } from "@shared/types";
import z from "zod";

const globalProps = { size: "small", fluid: true };
export const parameterInputs: InputConfig[] = [
   {
      component: PrimeVueInputs["InputText"],
      name: "title",
      placeholder: "Title",
      props: globalProps,
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["InputText"],
      name: "placeholder",
      placeholder: "Placeholder",
      props: globalProps,
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["InputText"],
      name: "unit",
      placeholder: "O'lchov birligi",
      props: globalProps,
      schema: z.string({ message: "Majburiy maydon!" }).optional().nullable(),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["Select"],
      name: "component",
      placeholder: "Input turi",
      props: { ...globalProps, options: Object.keys(Inputs) },
      schema: z.string({ message: "Majburiy maydon!" }),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["Select"],
      name: "type",
      placeholder: "Malumot turi",
      props: { ...globalProps, options: ["string", "number", "boolean", "array"] },
      schema: z.string({ message: "Majburiy maydon!" }),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["AutoComplete"],
      name: "options",
      placeholder: "Variantlar",
      props: { ...globalProps, multiple: true, typeahead: false },
      schema: z.array(z.string()).optional().nullable(),
      class: ["mb-4"],
   },
];

export const parameterColumns = [
   { field: "id", header: "ID" },
   { field: "title", header: "Title" },
   { field: "placeholder", header: "Placeholder" },
   { field: "component", header: "Input turi" },
   { field: "type", header: "Malumot turi" },
   { field: "unit", header: "O'lchov birligi" },
   {
      field: "options",
      header: "Variantlar",
      formatter: (items: any[]): any[] => items,
   },
];

export const superRefine = (data, ctx) => {
   // Agar turi Select yoki SelectButton bo'lsa va options bo'sh bo'lsa
   const isSelect = ["Select", "SelectButton"].includes(data.type?.value || data.type);
   const options = data.options as any[];
   if (isSelect && (!options || options.length === 0)) {
      ctx.addIssue({
         code: z.ZodIssueCode.custom,
         message: "Select turi uchun variantlar majburiy!",
         path: ["options"], // Xatolik 'options' inputida chiqadi
      });
   }
};

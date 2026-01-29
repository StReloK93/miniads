import { Inputs } from "@/modules/Inputs";
import { InputConfig } from "@shared/types";
import z from "zod";

export const parameterInputs: InputConfig[] = [
   {
      component: Inputs["InputText"],
      name: "placeholder",
      placeholder: "Nomi",
      props: { placeholder: "Nomi" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: Inputs["InputText"],
      name: "unit",
      placeholder: "O'lchov birligi",
      props: { placeholder: "O'lchov birligi" },
      schema: z.string({ message: "Majburiy maydon!" }).optional().nullable(),
      class: ["mb-4"],
   },
   {
      component: Inputs["Select"],
      name: "component",
      placeholder: "Input turi",
      props: { options: Object.keys(Inputs) },
      schema: z.string({ message: "Majburiy maydon!" }),
      class: ["mb-4"],
   },
   {
      component: Inputs["Select"],
      name: "type",
      placeholder: "Malumot turi",
      props: { options: ["string", "number", "boolean", "array"] },
      schema: z.string({ message: "Majburiy maydon!" }),
      class: ["mb-4"],
   },
   {
      component: Inputs["AutoComplete"],
      name: "options",
      placeholder: "Variantlar",
      props: { multiple: true, typeahead: false },
      schema: z.array(z.string()).optional().nullable(),
      class: ["mb-4"],
   },
];

export const parameterColumns = [
   { field: "id", header: "ID" },
   { field: "placeholder", header: "Nomi" },
   { field: "type", header: "Input turi" },
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

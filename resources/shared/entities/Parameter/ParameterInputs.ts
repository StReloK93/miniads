import AdminField from "@shared/ui/AdminField.vue";
import { Inputs } from "@shared/ui/inputs";
import { InputConfig } from "@shared/types";
import z from "zod";

export const parameterInputs: InputConfig[] = [
   {
      component: AdminField,
      name: "title",
      placeholder: "Title",
      props: { adminKind: "text" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "placeholder",
      placeholder: "Placeholder",
      props: { adminKind: "text" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "unit",
      placeholder: "O'lchov birligi",
      props: { adminKind: "text" },
      schema: z.string({ message: "Majburiy maydon!" }).optional().nullable(),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "component",
      placeholder: "Input turi",
      props: { adminKind: "select", options: Object.keys(Inputs) },
      schema: z.string({ message: "Majburiy maydon!" }),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "type",
      placeholder: "Malumot turi",
      props: { adminKind: "select", options: ["string", "number", "boolean", "array"] },
      schema: z.string({ message: "Majburiy maydon!" }),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "options",
      placeholder: "Variantlar",
      props: { adminKind: "tags" },
      schema: z.array(z.string()).optional().nullable(),
      class: ["mb-4"],
   },
];

// Birinchi ustun — nom: telefonda jadval kartaga aylanganda u karta sarlavhasi bo'ladi
export const parameterColumns = [
   { field: "title", header: "Nomi" },
   { field: "placeholder", header: "Namuna matn" },
   { field: "component", header: "Input turi" },
   { field: "type", header: "Ma'lumot turi" },
   { field: "unit", header: "O'lchov birligi" },
   {
      field: "options",
      header: "Variantlar",
      formatter: (items: any[]): any[] => items,
   },
   { field: "id", header: "ID" },
];

export const superRefine = (data: Record<string, unknown>, ctx: z.RefinementCtx) => {
   const isSelect = data.component === "FieldSelect";
   const options = Array.isArray(data.options) ? data.options : [];
   if (isSelect && options.length === 0) {
      ctx.addIssue({
         code: z.ZodIssueCode.custom,
         message: "Select turi uchun variantlar majburiy!",
         path: ["options"],
      });
   }
};

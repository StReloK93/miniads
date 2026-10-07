import AdminField from "@shared/ui/AdminField.vue";
import { Inputs } from "@/modules/Inputs";
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

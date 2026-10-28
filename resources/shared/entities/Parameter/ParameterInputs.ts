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
   ...translationInputs(),
];

/**
 * Tarjimalar (ru, en): bo'sh qolsa ilovada o'zbekchasi ko'rinadi.
 * Variantlar tarjimasi asl ro'yxat bilan bir xil tartibda va sonda yoziladi.
 */
function translationInputs(): InputConfig[] {
   const fields = [
      { name: "title", label: "Nomi", kind: "text" },
      { name: "placeholder", label: "Placeholder", kind: "text" },
      { name: "unit", label: "O'lchov birligi", kind: "text" },
      { name: "options", label: "Variantlar", kind: "tags" },
   ];

   return ["ru", "en"].flatMap((locale) =>
      fields.map((field) => ({
         component: AdminField,
         name: `${field.name}_${locale}`,
         placeholder: `${field.label} (${locale})`,
         props: { adminKind: field.kind },
         schema: field.kind === "tags" ? z.array(z.string()).optional().nullable() : z.string().trim().optional().nullable(),
         class: ["mb-4"],
      })),
   );
}

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

import { Inputs } from "@/modules/Inputs";
import { InputConfig } from "@shared/types";
import z from "zod";

export const globalProps = { size: "sm" };

export const productInputs: InputConfig[] = [
   {
      component: Inputs["InputImage"],
      name: "images",
      props: {
         multiple: true,
      },
      schema: z.array(z.any()).min(1, "Kamida 1 ta rasm yuklash kerak!"),
      class: ["mb-3"],
   },
   {
      component: Inputs["InputText"],
      name: "title",
      props: { placeholder: "Sarlavha" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(5, "5 ta simboldan ko'p bolishi kerak!"),
      class: ["mb-3"],
   },
   // {
   //    component: Inputs["Textarea"],
   //    name: "description",
   //    placeholder: "Izoh",
   //    props: globalProps,
   //    schema: z.string({ message: "Majburiy maydon!" }).trim().min(5, "5 ta simboldan ko'p bolishi kerak!"),
   //    class: ["mb-1.5"],
   // },
   {
      component: Inputs["InputText"],
      name: "phone",
      props: { placeholder: "Telefon raqam" },
      schema: z.string().optional().nullable(),
      class: ["mb-3"],
   },
];

export const ZodTypeMapping: Record<string, (required: boolean, label: string) => any> = {
   string: (required, label) => {
      const s = z.string("Majburiy maydon!").trim();
      return required ? s.min(1, `${label} to'ldirilishi shart`) : s.optional().nullable().or(z.literal(""));
   },
   number: (required, label) => {
      const n = z.coerce.number("Majburiy maydon!").min(1, `${label} Majburiy maydon!`);

      return required ? n : n.optional().nullable();
   },
   boolean: (required, label) => {
      const b = z.coerce.boolean();
      return required ? b.refine((val) => val === true, { message: `${label} tanlanishi shart` }) : b.default(false);
   },
};

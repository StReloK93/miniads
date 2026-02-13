import { Inputs } from "@/modules/Inputs";
import { InputConfig } from "@shared/types";
import z from "zod";

export const globalProps = { size: "sm" };

export const productInputs: InputConfig[] = [
   {
      component: Inputs["FieldImage"],
      name: "images",
      props: {
         multiple: true,
         title: "Rasmlar",
      },
      schema: z.array(z.any()).min(1, "Kamida 1 ta rasm yuklash kerak!"),
      class: ["mb-3"],
   },
   {
      component: Inputs["FieldText"],
      name: "title",
      props: { title: "Sarlavha", placeholder: "Masalan: Iphone 13 Pro Max" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(5, "5 ta simboldan ko'p bolishi kerak!"),
      class: ["mb-3"],
   },
   {
      component: Inputs["FieldTextarea"],
      name: "description",
      props: { title: "Izoh", maxHeight: 120, placeholder: "Eloningiz  haqida qo'shimcha ma'lumot" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(5, "5 ta simboldan ko'p bolishi kerak!"),
      class: ["mb-3"],
   },
   {
      component: Inputs["FieldText"],
      name: "phone",
      props: { title: "Telefon raqam", placeholder: "99-999-99-99" },
      schema: z.string().optional().nullable(),
      class: ["mb-3"],
   },
];

export const ZodTypeMapping: Record<string, (required: boolean, label: string) => any> = {
   string: (required, label) => {
      const s = z.string({ message: "Majburiy maydon!" }).trim();
      return required ? s.min(1, `${label} to'ldirilishi shart`) : s.optional().nullable().or(z.literal(""));
   },
   number: (required, label) => {
      const n = z.coerce.number({ message: "Majburiy maydon!" }).min(1, `${label} Majburiy maydon!`);

      return required ? n : n.optional().nullable();
   },
   boolean: (required, label) => {
      const b = z.coerce.boolean();
      return required ? b.refine((val) => val === true, { message: `${label} tanlanishi shart` }) : b.default(false);
   },
};

import { PrimeVueInputs } from "@/modules/PrimeVueInputs";
import { InputConfig } from "@/types";
import z from "zod";

export const globalProps = { size: "small", fluid: true };
export const schemaProps = {
   optional: z.any(),
   required: z.any(),
};
export const productInputs: InputConfig[] = [
   {
      component: PrimeVueInputs["InputText"],
      name: "title",
      placeholder: "Sarlavha",
      props: globalProps,
      schema: z
         .string({ message: "Majburiy maydon!" })
         .trim()
         .min(5, "5 ta simboldan ko'p bolishi kerak!"),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["Textarea"],
      name: "description",
      placeholder: "Izoh",
      props: globalProps,
      schema: z
         .string({ message: "Majburiy maydon!" })
         .trim()
         .min(5, "5 ta simboldan ko'p bolishi kerak!"),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["Textarea"],
      name: "phone",
      placeholder: "Telefon raqam",
      props: globalProps,
      schema: z.string().optional().nullable(),
      class: ["mb-4"],
   },
];

export const ZodTypeMapping: Record<string, (required: boolean, label: string) => any> = {
   // STRING uchun validatsiya
   string: (required, label) => {
      let s = z.string("Majburiy maydon!").trim();
      return required
         ? s.min(1, `${label} to'ldirilishi shart`)
         : s.optional().nullable().or(z.literal(""));
   },

   // NUMBER uchun validatsiya
   number: (required, label) => {
      // z.coerce ishlatamiz, chunki inputdan yoki bazadan string ko'rinishida kelishi mumkin
      let n = z.coerce.number("Majburiy maydon!").min(1, `Majburiy maydon!`);

      return required ? n : n.optional().nullable();
   },

   // BOOLEAN uchun validatsiya
   boolean: (required, label) => {
      // 0, 1, "true", "false" qiymatlarini haqiqiy booleanga o'giradi
      let b = z.coerce.boolean();
      return required
         ? b.refine((val) => val === true, { message: `${label} tanlanishi shart` })
         : b.default(false);
   },
};

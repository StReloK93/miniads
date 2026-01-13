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

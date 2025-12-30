import { PrimeVueInputs } from "@/configs/PrimeVueInputs";
import { InputConfig } from "@/types";
import z from "zod";

const globalProps = { size: "small", fluid: true };
export const categoryInputs: InputConfig[] = [
   {
      component: PrimeVueInputs["InputText"],
      name: "name",
      props: async function () {
         return { placeholder: "Nomi", ...globalProps };
      },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["InputText"],
      name: "slug",
      props: async function () {
         return { placeholder: "Dasturdagi url", ...globalProps };
      },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["Select"],
      name: "parent_id",
      props: async function () {
         return {
            placeholder: "Qaysi kategoriyaga tegishli",
            disabled: true,
            ...globalProps,
         };
      },
      schema: z.number().nullable().optional(),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["CustomUpload"],
      name: "file",
      props: async function () {
         return {
            placeholder: "file",
            ...globalProps,
         };
      },

      schema: z.instanceof(File).nullable().optional(),
      class: ["mb-4"],
   },
];

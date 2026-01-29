import { Inputs } from "@/modules/Inputs";
import { InputConfig } from "@shared/types";
import z from "zod";

const globalProps = { size: "small", fluid: true };
export const categoryInputs: InputConfig[] = [
   {
      component: Inputs["InputText"],
      name: "name",
      placeholder: "Nomi",
      props: globalProps,
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: Inputs["ToggleButton"],
      name: "is_page",
      props: {
         ...globalProps,
         onLabel: "Sahifa",
         offLabel: "Sahifa emas",
         onIcon: "pi pi-circle",
         offIcon: "pi pi-circle-fill",
      },
      schema: z.boolean().optional(),
      class: ["mb-4"],
   },
   {
      component: Inputs["ImageUpload"],
      name: "image",
      props: globalProps,
      schema: z
         .union([
            z.instanceof(File, { message: "Fayl bo'lishi shart" }),
            z.string().min(1, { message: "Yozuv bo'sh bo'lmasligi kerak" }),
         ])
         .optional()
         .nullable(),
      class: ["mb-4"],
   },
];

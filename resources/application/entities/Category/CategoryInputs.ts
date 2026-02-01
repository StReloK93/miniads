import { Inputs } from "@/modules/Inputs";
import { InputConfig } from "@shared/types";
import z from "zod";

export const categoryInputs: InputConfig[] = [
   {
      component: Inputs["FieldText"],
      name: "name",
      props: { placeholder: "Nomi" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   // {
   //    component: Inputs["ToggleButton"],
   //    name: "is_page",
   //    props: {
   //       ...globalProps,
   //       onLabel: "Sahifa",
   //       offLabel: "Sahifa emas",
   //       onIcon: "pi pi-circle",
   //       offIcon: "pi pi-circle-fill",
   //    },
   //    schema: z.boolean().optional(),
   //    class: ["mb-4"],
   // },
   {
      component: Inputs["FieldImage"],
      name: "image",
      props: {},
      schema: z.union([z.instanceof(File), z.string().min(1), z.null()]).optional(),
      class: ["mb-4"],
   },
];

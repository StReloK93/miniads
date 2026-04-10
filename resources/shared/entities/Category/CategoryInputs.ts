import { PrimeVueInputs } from "@admin/modules/PrimeVueInputs";
import { InputConfig } from "@shared/types";
import z from "zod";

const globalProps = { size: "small", fluid: true };
export const categoryInputs: InputConfig[] = [
   {
      component: PrimeVueInputs["InputText"],
      name: "name",
      placeholder: "Nomi",
      props: globalProps,
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["ToggleButton"],
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
      component: PrimeVueInputs["ToggleButton"],
      name: "with_price",
      props: {
         ...globalProps,
         onLabel: "Narx ko'rsatiladi",
         offLabel: "Narx ko'rsatilmaydi",
         onIcon: "pi pi-circle",
         offIcon: "pi pi-circle-fill",
      },
      schema: z.boolean().optional(),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["ToggleButton"],
      name: "with_image",
      props: {
         ...globalProps,
         onLabel: "Rasm ko'rsatiladi",
         offLabel: "Rasm ko'rsatilmaydi",
         onIcon: "pi pi-circle",
         offIcon: "pi pi-circle-fill",
      },
      schema: z.boolean().optional(),
      class: ["mb-4"],
   },
   {
      component: PrimeVueInputs["InputNumber"],
      name: "listing_duration_days",
      placeholder: "E'lon davomiyligi 5-20 kun",
      props: { ...globalProps, min: 5, max: 20 },
      schema: z.coerce.number({ message: "Majburiy maydon!" }).min(1, "Davomiyligi 1 dan katta bo'lishi kerak!"),
      class: ["mb-4"],
   },
   // {
   //    component: PrimeVueInputs["FieldNumber"],
   //    name: "listing_duration_days",
   //    props: { title: "E'lon davomiyligi (kun)", placeholder: "Masalan: 7", min: 1, max: 365 },
   //    schema: z.coerce.number({ message: "Majburiy maydon!" }).min(1, "Davomiyligi 1 dan katta bo'lishi kerak!"),
   //    class: ["mb-3"],
   // },

   {
      component: PrimeVueInputs["ImageUpload"],
      name: "image",
      props: {},
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

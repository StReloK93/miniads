import AdminField from "@shared/ui/AdminField.vue";
import { InputConfig } from "@shared/types";
import z from "zod";

export const categoryInputs: InputConfig[] = [
   {
      component: AdminField,
      name: "name",
      placeholder: "Nomi",
      props: { adminKind: "text" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(1, "Majburiy maydon!"),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "is_page",
      props: {
         adminKind: "toggle",
         onLabel: "Sahifa",
         offLabel: "Sahifa emas",
      },
      schema: z.boolean().optional(),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "with_price",
      props: {
         adminKind: "toggle",
         onLabel: "Narx ko'rsatiladi",
         offLabel: "Narx ko'rsatilmaydi",
      },
      schema: z.boolean().optional(),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "with_image",
      props: {
         adminKind: "toggle",
         onLabel: "Rasm ko'rsatiladi",
         offLabel: "Rasm ko'rsatilmaydi",
      },
      schema: z.boolean().optional(),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "listing_duration_days",
      placeholder: "E'lon davomiyligi 5-20 kun",
      props: { adminKind: "number", min: 5, max: 20 },
      schema: z.coerce.number({ message: "Majburiy maydon!" }).min(1, "Davomiyligi 1 dan katta bo'lishi kerak!"),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "image",
      props: { adminKind: "file" },
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

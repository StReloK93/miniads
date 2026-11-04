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
   // Tarjimalar: ilova shu tilda ochilganda ko'rinadi; bo'sh qolsa — o'zbekchasi
   {
      component: AdminField,
      name: "name_ru",
      placeholder: "Nomi (ru)",
      props: { adminKind: "text" },
      schema: z.string().trim().optional().nullable(),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "name_en",
      placeholder: "Nomi (en)",
      props: { adminKind: "text" },
      schema: z.string().trim().optional().nullable(),
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
   // O'chirilsa e'lon formasida "Sarlavha" maydoni chiqmaydi — sarlavha parametrlardan yasaladi
   // (masalan Naqd-Plastik: "Naqd → Plastik · 1 000 000 so'm")
   {
      component: AdminField,
      name: "with_title",
      value: true,
      props: {
         adminKind: "toggle",
         onLabel: "Sarlavha so'raladi",
         offLabel: "Sarlavha so'ralmaydi (parametrlardan yasaladi)",
      },
      schema: z.boolean().optional(),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "listing_duration_days",
      placeholder: "E'lon davomiyligi (kun)",
      props: { adminKind: "number", min: 1, max: 365 },
      schema: z.coerce.number({ message: "Majburiy maydon!" }).min(1, "Davomiyligi 1 dan katta bo'lishi kerak!"),
      class: ["mb-4"],
   },
   // Yoqilsa davomiylik hisobga olinmaydi: e'lonlar o'zi o'chirilmaguncha turadi (masalan Kafe-restoranlar)
   {
      component: AdminField,
      name: "is_permanent",
      props: {
         adminKind: "toggle",
         onLabel: "Muddatsiz e'lonlar",
         offLabel: "E'lonlar muddati bor",
      },
      schema: z.boolean().optional(),
      class: ["mb-4"],
   },
   // Bosh sahifadagi "Tezkor bo'limlar" qatori (kategoriyalar tagida)
   {
      component: AdminField,
      name: "is_featured",
      props: {
         adminKind: "toggle",
         onLabel: "Tezkor bo'limlarda chiqadi",
         offLabel: "Tezkor bo'limlarda chiqmaydi",
      },
      schema: z.boolean().optional(),
      class: ["mb-4"],
   },
   {
      component: AdminField,
      name: "featured_order",
      placeholder: "Tezkor bo'limdagi tartibi (1, 2, 3...)",
      props: { adminKind: "number", min: 0, max: 999 },
      schema: z.coerce.number().min(0).optional().nullable(),
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

import { Inputs } from "@/modules/Inputs";
import { api } from "@shared/composables/useFetch";
import { InputConfig } from "@shared/types";
import z from "zod";

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
      component: Inputs["FieldNumber"],
      name: "price",
      props: { title: "Narx", placeholder: "Masalan: 1 000 000", min: 0, max: 9999999999999 },
      schema: z.coerce.number({ message: "Majburiy maydon!" }).min(1, "Narx 1 dan katta bo'lishi kerak!"),
      class: ["mb-3"],
      teleport_parent_class: "parent_price",
   },
   {
      component: Inputs["FieldSelect"],
      name: "price_type_id",
      generateProps: async function () {
         const { data } = await api.get("/price-types");
         this.props = {
            options: data,
            value: "name",
            selectIcon: false,
         };
      },
      value: 1,
      schema: z.number({ message: "Majburiy maydon!" }),
      class: ["w-22", "absolute", "top-0.5", "right-0"],
      teleport_child_class: "parent_price",
   },
   {
      component: Inputs["FieldTextarea"],
      name: "description",
      props: { title: "Izoh", maxHeight: 120, placeholder: "Eloningiz  haqida qo'shimcha ma'lumot" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(5, "5 ta simboldan ko'p bolishi kerak!"),
      class: ["mb-3"],
   },
   {
      component: Inputs["FieldMask"],
      name: "phone",
      props: { title: "Telefon raqam", placeholder: "93-123-45-67", mask: "99-999-99-99", inputmode: "tel" },
      schema: z.string().optional().nullable(),
   },
];

export const ZodTypeMapping: Record<string, (required: boolean) => any> = {
   string: (required) => {
      const s = z.string({ message: "Majburiy maydon!" }).trim();
      return required ? s.min(1, `To'ldirilishi shart`) : s.optional().nullable().or(z.literal(""));
   },
   number: (required) => {
      const n = z.coerce.number({ message: "Majburiy maydon!" }).min(1, `Majburiy maydon!`);

      return required ? n : n.optional().nullable();
   },
   boolean: (required) => {
      const b = z.coerce.boolean();
      return required ? b.refine((val) => val === true, { message: `Tanlanishi shart` }) : b.default(false);
   },
};

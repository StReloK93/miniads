import { Inputs } from "@/modules/Inputs";
import { api } from "@shared/composables/useFetch";
import { InputConfig } from "@shared/types";
import z from "zod";

let cachedPriceTypes: any[] | null = null;

export async function getPriceTypes() {
   if (!cachedPriceTypes) {
      const { data } = await api.get("/price-types");
      cachedPriceTypes = data;
   }
   return cachedPriceTypes;
}

export interface ProductInputOptions {
   withPrice?: boolean;
   withImage?: boolean;
   priceTypes?: any[];
}

export function productInputs(options: ProductInputOptions = {}): InputConfig[] {
   const inputs: InputConfig[] = [];

   // Rasmlar
   if (options.withImage !== false) {
      inputs.push({
         component: Inputs["FieldImage"],
         name: "images",
         props: {
            multiple: true,
            title: "Rasmlar",
         },
         schema: z.array(z.any()),
         class: ["mb-3"],
      });
   }

   // Sarlavha
   inputs.push({
      component: Inputs["FieldText"],
      name: "title",
      props: { title: "Sarlavha", placeholder: "Eloningiz sarlavhasi" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(5, "5 ta simboldan ko'p bolishi kerak!"),
      class: ["mb-3"],
   });

   // Narx va Narx turi
   if (options.withPrice !== false) {
      inputs.push(
         {
            component: Inputs["FieldNumber"],
            name: "price",
            props: {
               title: "Narx",
               placeholder: "Masalan: 1 000 000",
               min: 0,
               max: 9999999999999,
               inputmode: "numeric",
               class: "pr-28!",
            },
            schema: z.coerce.number({ message: "Majburiy maydon!" }).min(1, "Narx 1 dan katta bo'lishi kerak!"),
            class: ["mb-3"],
            teleport_parent_class: "parent_price",
         },
         {
            component: Inputs["FieldSelect"],
            name: "price_type_id",
            generateProps: async function () {
               const types = options.priceTypes || (await getPriceTypes());
               this.props = {
                  options: types,
                  value: "name",
                  selectIcon: false,
                  variant: "addon",
               };
            },
            props: options.priceTypes
               ? {
                    options: options.priceTypes,
                    value: "name",
                    selectIcon: false,
                    variant: "addon",
                 }
               : undefined,
            value: 1,
            schema: z.number({ message: "Majburiy maydon!" }),
            class: ["absolute", "inset-y-0", "right-0", "flex", "items-center", "pr-2"],
            teleport_child_class: "parent_price",
         }
      );
   }

   // Tavsif
   inputs.push({
      component: Inputs["FieldTextarea"],
      name: "description",
      props: { title: "Izoh", maxHeight: 120, placeholder: "Eloningiz  haqida qo'shimcha ma'lumot" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().optional().nullable(),
      class: ["mb-3"],
   });

   // Telefon
   inputs.push({
      component: Inputs["FieldMask"],
      name: "phone",
      props: { title: "Telefon raqam", placeholder: "93-123-45-67", mask: "99-999-99-99", inputmode: "tel" },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(9, "To'liq telefon raqamini kiriting!"),
   });

   return inputs;
}

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

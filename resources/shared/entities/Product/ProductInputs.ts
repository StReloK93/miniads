import { Inputs } from "@shared/ui/inputs";
import { api } from "@shared/composables/useFetch";
import { InputConfig } from "@shared/types";
import { useAuth } from "@shared/store/useAuth";
import DistrictRepo from "@shared/entities/District/DistrictRepo";
import z from "zod";
import { t } from "@shared/i18n";

let cachedPriceTypes: any[] | null = null;
let cachedDistricts: any[] | null = null;

export async function getPriceTypes() {
   if (!cachedPriceTypes) {
      const { data } = await api.get("/price-types");
      cachedPriceTypes = data;
   }
   return cachedPriceTypes;
}

export async function getDistricts() {
   if (!cachedDistricts) {
      const { data } = await DistrictRepo.index();
      cachedDistricts = data;
   }
   return cachedDistricts;
}

export interface ProductInputOptions {
   withPrice?: boolean;
   withImage?: boolean;
   priceTypes?: any[];
   hasTelegramUsername?: boolean;
   telegramUsername?: string | null;
   districts?: any[];
   defaultDistrictId?: number | null;
   /** Asosiy bo'lim ikonkasi (categories.image) — sarlavha namunasi uchun */
   rootIcon?: string | null;
}

// Sarlavha namunasi asosiy bo'lim ikonkasi bo'yicha (categories.image) — nomga bog'liq emas, har tilda ishlaydi
const TITLE_EXAMPLES: Record<string, "phone" | "car" | "realty" | "job" | "service" | "clothes" | "furniture"> = {
   MonitorSmartphone: "phone",
   Car: "car",
   House: "realty",
   BriefcaseBusiness: "job",
   Wrench: "service",
   Shirt: "clothes",
   Armchair: "furniture",
};

export function getDynamicTitlePlaceholder(rootIcon?: string | null): string {
   const key = (rootIcon && TITLE_EXAMPLES[rootIcon]) || "default";
   return t("common.example", { value: t(`form.examples.${key}`) });
}

export function productInputs(options: ProductInputOptions = {}): InputConfig[] {
   const inputs: InputConfig[] = [];

   // Rasmlar
   if (options.withImage !== false) {
      inputs.push({
         component: Inputs["FieldImage"],
         name: "images",
         required: true,
         props: {
            multiple: true,
            title: t("form.photos"),
         },
         schema: z.array(z.any()),
         class: ["mb-3"],
      });
   }

   // Sarlavha
   inputs.push({
      component: Inputs["FieldText"],
      name: "title",
      required: true,
      props: {
         title: t("form.title"),
         placeholder: getDynamicTitlePlaceholder(options.rootIcon),
      },
      schema: z.string({ message: t("form.required") }).trim().min(5, t("form.minChars", { n: 5 })),
      class: ["mb-3"],
   });

   // Narx va Narx turi
   if (options.withPrice !== false) {
      inputs.push(
         {
            component: Inputs["FieldNumber"],
            name: "price",
            required: true,
            props: {
               title: t("common.price"),
               placeholder: t("common.example", { value: "1 000 000" }),
               min: 0,
               max: 9999999999999,
               inputmode: "numeric",
               class: "pr-28!",
            },
            schema: z.coerce.number({ message: t("form.required") }).min(1, t("form.priceMin")),
            class: ["mb-3"],
            teleport_parent_class: "parent_price",
         },
         {
            component: Inputs["FieldSelect"],
            name: "price_type_id",
            required: true,
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
            schema: z.number({ message: t("form.required") }),
            class: ["absolute", "inset-y-0", "right-0", "flex", "items-center", "pr-2"],
            teleport_child_class: "parent_price",
         },
      );
   }

   // Tavsif
   inputs.push({
      component: Inputs["FieldTextarea"],
      name: "description",
      required: false,
      optional: true,
      props: {
         title: t("common.description"),
         rows: 4,
         maxHeight: 150,
         placeholder: t("form.descriptionPlaceholder"),
      },
      schema: z.string({ message: t("form.required") }).trim().optional().nullable(),
      class: ["mb-3"],
   });

   // Shahar / Tuman
   inputs.push({
      component: Inputs["FieldSelect"],
      name: "district_id",
      required: true,
      generateProps: async function () {
         const districts = options.districts || (await getDistricts());
         this.props = {
            title: t("district.field"),
            placeholder: t("district.choose"),
            options: districts,
            value: "name",
         };
      },
      props: options.districts
         ? {
              title: t("district.field"),
              placeholder: t("district.choose"),
              options: options.districts,
              value: "name",
           }
         : {
              title: t("district.field"),
              placeholder: t("district.choose"),
              options: [],
              value: "name",
           },
      value: options.defaultDistrictId !== undefined ? options.defaultDistrictId : 1,
      schema: z.coerce.number({ message: t("district.choose") }).min(0, t("district.choose")),
      class: ["mb-3"],
   });

   // Telefon / Aloqa
   let hasTelegram = options.hasTelegramUsername;
   let telegramUsername = options.telegramUsername;

   try {
      const auth = useAuth();
      if (hasTelegram === undefined) {
         hasTelegram = Boolean(auth.user?.username);
      }
      if (telegramUsername === undefined) {
         telegramUsername = auth.user?.username;
      }
   } catch {
      // Pinia might not be ready yet in tests or SSR
   }

   inputs.push({
      component: Inputs["FieldPhone"],
      name: "phone",
      required: true,
      props: {
         title: t("form.contact"),
         placeholder: "93-123-45-67",
         mask: "99-999-99-99",
         inputmode: "tel",
         telegramUsername,
      },
      schema: z
         .string({ message: t("form.phoneRequired") })
         .trim()
         .superRefine((val, ctx) => {
            if (val === "telegram") {
               return;
            }
            const raw = val ? val.replace(/\D/g, "") : "";
            if (!raw) {
               ctx.addIssue({
                  code: z.ZodIssueCode.custom,
                  message: t("form.phoneIncomplete"),
               });
               return;
            }
            if (raw.length < 9) {
               ctx.addIssue({
                  code: z.ZodIssueCode.custom,
                  message: t("form.phoneIncomplete"),
               });
            }
         }),
   });

   return inputs;
}

export const ZodTypeMapping: Record<string, (required: boolean) => any> = {
   string: (required) => {
      const s = z.string({ message: t("form.required") }).trim();
      return required ? s.min(1, t("form.required")) : s.optional().nullable().or(z.literal(""));
   },
   number: (required) => {
      const n = z.coerce.number({ message: t("form.required") }).min(1, t("form.required"));

      return required ? n : n.optional().nullable();
   },
   boolean: (required) => {
      const b = z.coerce.boolean();
      return required ? b.refine((val) => val === true, { message: t("form.mustSelect") }) : b.default(false);
   },
};

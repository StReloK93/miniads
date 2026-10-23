import { Inputs } from "@/modules/Inputs";
import { api } from "@shared/composables/useFetch";
import { InputConfig } from "@shared/types";
import { useAuth } from "@shared/store/useAuth";
import DistrictRepo from "@shared/entities/District/DistrictRepo";
import z from "zod";

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
   categoryName?: string;
}

export function getDynamicTitlePlaceholder(categoryName?: string): string {
   if (!categoryName) return "Masalan: Mahsulot yoki xizmat nomi (holati, modeli)";
   const name = categoryName.toLowerCase();
   if (name.includes("telefon") || name.includes("smartfon") || name.includes("gadjet") || name.includes("elektronika")) {
      return "Masalan: iPhone 13 128GB Midnight ideal holatda";
   }
   if (name.includes("avto") || name.includes("mashina") || name.includes("transport")) {
      return "Masalan: Chevrolet Cobalt 2022 2-pozitsiya, oq rang";
   }
   if (name.includes("uy") || name.includes("ko'chmas") || name.includes("kvartira") || name.includes("yer") || name.includes("ijara")) {
      return "Masalan: 2 xonali kvartira, 3-qavat, ta'mirlangan";
   }
   if (name.includes("ish") || name.includes("vakansiya") || name.includes("xodim")) {
      return "Masalan: Tajribali sotuvchi-maslahatchi kerak";
   }
   if (name.includes("xizmat") || name.includes("ta'mir") || name.includes("usta")) {
      return "Masalan: Muzlatgich va konditsioner ta'mirlash";
   }
   if (name.includes("kiyim") || name.includes("poyabzal") || name.includes("moda")) {
      return "Masalan: Qishki kurtka yangi (L razmer, qora)";
   }
   if (name.includes("mebel") || name.includes("jihoz")) {
      return "Masalan: Yumshoq mebel garnituri (kam ishlatilgan)";
   }
   return "Masalan: Mahsulot yoki xizmat nomi (holati, modeli)";
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
      required: true,
      props: {
         title: "Sarlavha",
         placeholder: getDynamicTitlePlaceholder(options.categoryName),
      },
      schema: z.string({ message: "Majburiy maydon!" }).trim().min(5, "5 ta simboldan ko'p bolishi kerak!"),
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
            schema: z.number({ message: "Majburiy maydon!" }),
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
         title: "Izoh",
         maxHeight: 120,
         placeholder: "Mahsulot holati, xususiyatlari, yetkazib berish yoki boshqa muhim ma'lumotlar",
      },
      schema: z.string({ message: "Majburiy maydon!" }).trim().optional().nullable(),
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
            title: "Shahar / Tuman",
            placeholder: "Shaharni tanlang",
            options: districts,
            value: "name",
         };
      },
      props: options.districts
         ? {
              title: "Shahar / Tuman",
              placeholder: "Shaharni tanlang",
              options: options.districts,
              value: "name",
           }
         : {
              title: "Shahar / Tuman",
              placeholder: "Shaharni tanlang",
              options: [],
              value: "name",
           },
      value: options.defaultDistrictId !== undefined ? options.defaultDistrictId : 1,
      schema: z.coerce.number({ message: "Shaharni tanlang!" }).min(0, "Shaharni tanlang!"),
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
         title: "Bog'lanish uchun",
         placeholder: "93-123-45-67",
         mask: "99-999-99-99",
         inputmode: "tel",
         telegramUsername,
      },
      schema: z
         .string({ message: "Telefon raqamini kiriting!" })
         .trim()
         .superRefine((val, ctx) => {
            if (val === "telegram") {
               return;
            }
            const raw = val ? val.replace(/\D/g, "") : "";
            if (!raw) {
               ctx.addIssue({
                  code: z.ZodIssueCode.custom,
                  message: "Telefon raqamini to'liq kiriting!",
               });
               return;
            }
            if (raw.length < 9) {
               ctx.addIssue({
                  code: z.ZodIssueCode.custom,
                  message: "To'liq telefon raqamini kiriting (9 ta raqam)!",
               });
            }
         }),
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

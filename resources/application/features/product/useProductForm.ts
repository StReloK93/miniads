import { Component } from "vue";
import { ICategory, InputConfig, IProduct, IProductPayload } from "@shared/types";
import { Inputs } from "@shared/ui/inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import { useCity } from "@shared/entities/District/useCity";
import { useAuth } from "@shared/store/useAuth";
import { isYearParameter } from "@shared/modules/productSpecs";
import { useCategory } from "@shared/entities/Category/useCategory";
import { t } from "@shared/i18n";
import z from "zod";

/** Asosiy bo'lim ikonkasi (House, Car...) — sarlavha namunasi shundan tanlanadi */
function rootIconOf(category: ICategory): string | null {
   const roots = (useCategory().parentCategories ?? []) as ICategory[];
   return roots.find((root) => root.id === (category.root_id ?? category.id))?.image ?? null;
}

/**
 * E'lon yaratish va tahrirlash formasi uchun umumiy mantiq.
 * Farqi bitta: tahrirlashda `product` beriladi va maydonlar uning qiymatlari bilan to'ldiriladi.
 */
export async function buildProductFormInputs(
   category: ICategory,
   options: { districtId: number | null; product?: IProduct },
): Promise<InputConfig[]> {
   const authStore = useAuth();
   const cityStore = useCity();
   const { product } = options;

   if (!cityStore.cities?.length) {
      await cityStore.getCities();
   }

   const baseInputs = productInputs({
      withPrice: category.with_price !== false,
      withImage: category.with_image !== false,
      withTitle: category.with_title !== false,
      hasTelegramUsername: Boolean(authStore.user?.username),
      telegramUsername: authStore.user?.username,
      districts: cityStore.cities || undefined,
      defaultDistrictId: options.districtId,
      rootIcon: rootIconOf(category),
   });

   await Promise.all(baseInputs.map((input) => input.generateProps?.()));

   const parameters = category.parameters || [];
   const phoneInput = baseInputs.find((input) => input.name === "phone");
   if (phoneInput) {
      phoneInput.class = parameters.length ? ["mb-3"] : [];
      // Yangi e'lon: profildagi asosiy raqam (birinchi e'londa avtomatik saqlanadi, profilda o'zgartiriladi)
      if (!product && authStore.user?.phone) {
         phoneInput.value = authStore.user.phone;
      }
   }

   const parameterInputs: InputConfig[] = parameters.map((parameter, index) => {
      // Admin "majburiy" deb belgilagan parametr (masalan Naqd-Plastik: yo'nalish va summa)
      const required = Boolean(parameter.pivot?.is_required);
      return {
         component: Inputs[parameter.component] as Component,
         name: `parameter_${parameter.id}`,
         class: index === parameters.length - 1 ? [] : ["mb-3"],
         required,
         optional: !required,
         value: product
            ? product.parameter_values?.find((p) => p.parameter_id === parameter.id)?.value || ""
            : undefined,
         props: {
            title: parameter.title,
            placeholder: parameter.placeholder,
            // Ko'rinishi foydalanuvchi tilida, qiymati asl (o'zbekcha) — filtrlar shunga tayanadi
            options: (parameter.options || []).map((option, i) => ({
               value: option,
               label: parameter.option_labels?.[i] ?? option,
            })),
            inputmode: parameter.type === "number" ? "numeric" : undefined,
            // Yil "2 022" bo'lib ko'rinmasin; kelajak yilini ham yozib bo'lmaydi
            ...(isYearParameter(parameter) ? { grouping: false, max: new Date().getFullYear() + 1 } : {}),
         },
         schema: ZodTypeMapping[parameter.type](required),
      };
   });

   // Telegram assistent e'loni: aloqa — "@nick" (raqam emas). Admin tahrirlaganda telefon maskasi uni o'chirib yubormasin
   if (product?.phone?.startsWith("@") && phoneInput) {
      phoneInput.component = Inputs["FieldText"];
      phoneInput.props = { title: t("form.contact"), placeholder: "@username" };
      phoneInput.schema = z.string({ message: t("form.phoneRequired") }).trim().min(2, t("form.phoneRequired"));
   }

   if (product) {
      baseInputs.forEach((input) => {
         const value = product[input.name as keyof IProduct];
         if (value !== undefined) {
            input.value = value;
         }
      });
   }

   return [...baseInputs, ...parameterInputs];
}

/**
 * Forma qiymatlarini API kutayotgan ko'rinishga keltiradi.
 */
export function buildProductPayload(
   values: Record<string, any>,
   options: { categoryId: number | string | undefined; fallbackDistrictId: number | null },
): IProductPayload {
   const districtId = values.district_id !== undefined ? values.district_id : options.fallbackDistrictId;

   const parameters = Object.keys(values)
      .filter((key) => key.startsWith("parameter_"))
      .map((key) => ({ id: key.replace("parameter_", ""), value: values[key] }))
      .filter(({ value }) => value !== null && value !== undefined && value !== "");

   return {
      title: values.title,
      // "telegram" — foydalanuvchi telefon o'rniga Telegram username'ini tanlagan
      phone: values.phone === "telegram" || !values.phone ? null : values.phone,
      back_color_id: values.back_color_id,
      description: values.description,
      // Bo'sh narx — "Kelishiladi": null yuboramiz (tahrirlashda eski narx tozalansin)
      price: values.price ?? null,
      price_type_id: values.price_type_id,
      category_id: options.categoryId,
      parameters,
      district_id: districtId ? Number(districtId) : null,
      images: values.images,
   };
}

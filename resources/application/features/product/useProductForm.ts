import { Component } from "vue";
import { ICategory, InputConfig, IProduct, IProductPayload } from "@shared/types";
import { Inputs } from "@shared/ui/inputs";
import { productInputs, ZodTypeMapping } from "@shared/entities/Product/ProductInputs";
import { useCity } from "@shared/entities/District/useCity";
import { useAuth } from "@shared/store/useAuth";
import { isYearParameter } from "@shared/modules/productSpecs";

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
      hasTelegramUsername: Boolean(authStore.user?.username),
      telegramUsername: authStore.user?.username,
      districts: cityStore.cities || undefined,
      defaultDistrictId: options.districtId,
      categoryName: category.name,
   });

   await Promise.all(baseInputs.map((input) => input.generateProps?.()));

   const parameters = category.parameters || [];
   const phoneInput = baseInputs.find((input) => input.name === "phone");
   if (phoneInput) {
      phoneInput.class = parameters.length ? ["mb-3"] : [];
   }

   const parameterInputs: InputConfig[] = parameters.map((parameter, index) => ({
      component: Inputs[parameter.component] as Component,
      name: `parameter_${parameter.id}`,
      class: index === parameters.length - 1 ? [] : ["mb-3"],
      required: false,
      optional: true,
      value: product ? product.parameter_values?.find((p) => p.parameter_id === parameter.id)?.value || "" : undefined,
      props: {
         title: parameter.title,
         placeholder: parameter.placeholder,
         options: parameter.options || [],
         inputmode: parameter.type === "number" ? "numeric" : undefined,
         // Yil "2 022" bo'lib ko'rinmasin; kelajak yilini ham yozib bo'lmaydi
         ...(isYearParameter(parameter) ? { grouping: false, max: new Date().getFullYear() + 1 } : {}),
      },
      schema: ZodTypeMapping[parameter.type](false),
   }));

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
      price: values.price,
      price_type_id: values.price_type_id,
      category_id: options.categoryId,
      parameters,
      district_id: districtId ? Number(districtId) : null,
      images: values.images,
   };
}

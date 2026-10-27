import type { IParameterValue } from "@shared/types";
import { formatPrice } from "@shared/modules/formatters";
import { t } from "@shared/i18n";

// Kartadagi bitta qatorga sig'masa — bu xususiyat emas, tavsif (kartada kerak emas)
const MAX_VALUE_LENGTH = 22;

/** Asl (o'zbekcha) birlik — mantiq uchun; server tarjima qilgan `unit` faqat ko'rsatish uchun */
type ParameterUnit = { unit?: string | null; unit_code?: string | null } | undefined;
const unitCode = (parameter: ParameterUnit) => parameter?.unit_code ?? parameter?.unit ?? null;

/** Ko'rsatish uchun birlik: yil uchun birlik yozilmaydi ("2018" hamma tilda tushunarli) */
export function displayUnit(parameter: ParameterUnit): string | null {
   if (isYearParameter(parameter)) return null;
   return parameter?.unit?.replace(/^-/, "") || null;
}

function withUnit(value: string, parameter: ParameterUnit): string {
   const unit = displayUnit(parameter);
   if (!unit) return value;
   // O'zbekcha "-qavat" songa qo'shib yoziladi: "4-qavat"
   return parameter?.unit?.startsWith("-") ? `${value}-${unit}` : `${value} ${unit}`;
}

function formatValue(value: string, type: string): string {
   if (type !== "number") return value;
   const number = Number(value);
   // 85000 → "85 000"; yil (2018) ajratilmaydi
   return Number.isFinite(number) && number >= 10000 ? formatPrice(number) : value;
}

/**
 * E'lon kartasi uchun 2–3 ta asosiy xususiyat: "3 xona · 75 m² · 4/9 qavat", "2018 · 85 000 km".
 * Tartib — kategoriya parametrlari tartibi (eng muhimi birinchi keladi).
 */
export function productSpecs(values: IParameterValue[] | undefined, limit = 3): string[] {
   if (!values?.length) return [];

   const floor = values.find((v) => unitCode(v.parameter) === "-qavat");
   const totalFloors = values.find((v) => unitCode(v.parameter) === "qavatli");

   const specs: string[] = [];
   for (const item of values) {
      if (specs.length >= limit) break;

      const parameter = item.parameter;
      // Tanlov qiymati foydalanuvchi tilida (server: display_value)
      const value = String(item.display_value ?? item.value ?? "").trim();
      if (!parameter || !value || value.length > MAX_VALUE_LENGTH) continue;

      // Qavat va umumiy qavatlar birlashtiriladi: "4/9 qavat"
      if (floor && totalFloors && (item === floor || item === totalFloors)) {
         if (item === floor) specs.push(t("product.floors", { floor: floor.value, total: totalFloors.value }));
         continue;
      }

      specs.push(withUnit(formatValue(value, parameter.type), parameter));
   }

   return specs;
}

/** Yil parametri ("Ishlab chiqarilgan yili"): forma va filtrlarda mingliklarga ajratilmaydi */
export function isYearParameter(parameter: ParameterUnit): boolean {
   return unitCode(parameter) === "yil";
}

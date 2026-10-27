import type { IParameterValue } from "@shared/types";
import { formatPrice } from "@shared/modules/formatters";

// Kartadagi bitta qatorga sig'masa — bu xususiyat emas, tavsif (kartada kerak emas)
const MAX_VALUE_LENGTH = 22;

function withUnit(value: string, unit: string | null): string {
   if (!unit) return value;
   // "-qavat" kabi birliklar songa qo'shib yoziladi: "4-qavat"
   return unit.startsWith("-") ? `${value}${unit}` : `${value} ${unit}`;
}

function formatValue(value: string, type: string): string {
   if (type !== "number") return value;
   const number = Number(value);
   // 85000 → "85 000"; yil (2018) ajratilmaydi
   return Number.isFinite(number) && number >= 10000 ? formatPrice(number) : value;
}

/**
 * E'lon kartasi uchun 2–3 ta asosiy xususiyat: "3 xona · 75 m² · 4/9 qavat", "2018 yil · 85 000 km".
 * Tartib — kategoriya parametrlari tartibi (eng muhimi birinchi keladi).
 */
export function productSpecs(values: IParameterValue[] | undefined, limit = 3): string[] {
   if (!values?.length) return [];

   const floor = values.find((v) => v.parameter?.unit === "-qavat");
   const totalFloors = values.find((v) => v.parameter?.unit === "qavatli");

   const specs: string[] = [];
   for (const item of values) {
      if (specs.length >= limit) break;

      const parameter = item.parameter;
      const value = String(item.value ?? "").trim();
      if (!parameter || !value || value.length > MAX_VALUE_LENGTH) continue;

      // Qavat va umumiy qavatlar birlashtiriladi: "4/9 qavat"
      if (floor && totalFloors && (item === floor || item === totalFloors)) {
         if (item === floor) specs.push(`${floor.value}/${totalFloors.value} qavat`);
         continue;
      }

      specs.push(withUnit(formatValue(value, parameter.type), parameter.unit));
   }

   return specs;
}

/** Yil parametri ("Ishlab chiqarilgan yili"): forma va filtrlarda mingliklarga ajratilmaydi */
export function isYearParameter(parameter: { unit?: string | null } | undefined): boolean {
   return parameter?.unit === "yil";
}

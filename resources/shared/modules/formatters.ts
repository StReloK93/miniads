import { currentLocale, t } from "@shared/i18n";

export interface FormatPriceOptions {
   fallback?: string;
   unit?: string;
}

// Intl uchun: o'zbekcha sana "8-okt", ruscha "8 окт.", inglizcha "8 Oct"
const INTL_LOCALES = { uz: "uz-UZ", ru: "ru-RU", en: "en-GB" } as const;

/**
 * Narxni formatlash funksiyasi (masalan: 1 000 000 yoki 1 000 000 so'm).
 * Mingliklar hamma tilda bo'sh joy bilan — mahalliy odat.
 */
export function formatPrice(value?: number | null, options?: FormatPriceOptions): string {
   if (value === null || value === undefined) {
      return options?.fallback ?? "";
   }
   const formatted = value.toLocaleString("ru-RU");
   return options?.unit ? `${formatted} ${options.unit}` : formatted;
}

export interface PriceType {
   type: string;
   position?: "left" | "right";
}

/**
 * E'lon narxi o'z valyutasi bilan (masalan: 1 000 000 so'm yoki $ 500).
 * fallback berilmasa — "Kelishiladi" (joriy tilda).
 */
export function formatProductPrice(value?: number | null, priceType?: PriceType | null, fallback?: string): string {
   if (value === null || value === undefined) return fallback ?? t("listingPrice.negotiable");
   const formatted = formatPrice(value);
   if (!priceType?.type) return formatted;
   return priceType.position === "left" ? `${priceType.type} ${formatted}` : `${formatted} ${priceType.type}`;
}

/**
 * Vaqtni 'hozir', '10 minut oldin', 'kecha', '3 kun oldin' ko'rinishida formatlash
 */
export function timeAgo(date: Date | string | number): string {
   if (!date) return "";
   const d = new Date(date);
   const now = new Date();

   // Kunlarni faqat date bo'yicha solishtiramiz
   const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
   const startOfDate = new Date(d.getFullYear(), d.getMonth(), d.getDate());

   const dayDiff = Math.floor((startOfToday.getTime() - startOfDate.getTime()) / 86400000);

   // Bugun
   if (dayDiff === 0) {
      const diffMs = now.getTime() - d.getTime();
      const minutes = Math.floor(diffMs / 60000);
      const hours = Math.floor(minutes / 60);

      if (minutes < 1) return t("time.now");
      if (minutes < 60) return t("time.minutesAgo", minutes);
      return t("time.hoursAgo", hours);
   }

   // Kecha
   if (dayDiff === 1) {
      return t("time.yesterday");
   }

   // 2+ kun
   return t("time.daysAgo", dayDiff);
}

/**
 * Sanani joriy til formatida (masalan: 8-okt, 2026)
 */
export function formatDate(
   value: string | Date | null | undefined,
   dateStyle: "full" | "long" | "medium" | "short" = "medium"
): string {
   if (!value) return "";
   return new Intl.DateTimeFormat(INTL_LOCALES[currentLocale()], { dateStyle }).format(new Date(value));
}

/**
 * Sana va vaqtni formatlash (masalan: 8-okt, 16:30)
 */
export function formatDateTime(value: string | Date | null | undefined): string {
   if (!value) return "—";
   return new Intl.DateTimeFormat(INTL_LOCALES[currentLocale()], {
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
   }).format(new Date(value));
}

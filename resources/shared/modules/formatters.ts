export interface FormatPriceOptions {
   fallback?: string;
   unit?: string;
}

/**
 * Narxni formatlash funksiyasi (masalan: 1 000 000 yoki 1 000 000 so'm)
 */
export function formatPrice(value?: number | null, options?: FormatPriceOptions): string {
   if (value === null || value === undefined) {
      return options?.fallback ?? "";
   }
   const formatted = value.toLocaleString("ru-RU");
   return options?.unit ? `${formatted} ${options.unit}` : formatted;
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

      if (minutes < 1) return "hozir";
      if (minutes < 60) return `${minutes} minut oldin`;
      return `${hours} soat oldin`;
   }

   // Kecha
   if (dayDiff === 1) {
      return "kecha";
   }

   // 2+ kun
   return `${dayDiff} kun oldin`;
}

/**
 * Sanani o'zbek formatida formatlash (masalan: 8-okt, 2026)
 */
export function formatDate(
   value: string | Date | null | undefined,
   dateStyle: "full" | "long" | "medium" | "short" = "medium"
): string {
   if (!value) return "";
   return new Intl.DateTimeFormat("uz-UZ", { dateStyle }).format(new Date(value));
}

/**
 * Sana va vaqtni formatlash (masalan: 8-okt, 16:30)
 */
export function formatDateTime(value: string | Date | null | undefined): string {
   if (!value) return "—";
   return new Intl.DateTimeFormat("uz-UZ", {
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
   }).format(new Date(value));
}

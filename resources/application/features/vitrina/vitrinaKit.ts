import { Building2, Images, ReceiptText, Store, UtensilsCrossed } from "lucide-vue-next";
import { isTMA, postEvent } from "@tma.js/bridge";
import { formatProductPrice } from "@shared/modules/formatters";
import { t } from "@shared/i18n";
import type { IVitrinaItem, VitrinaType } from "@shared/types";

/**
 * Vitrina turlari: belgisi, rangi va mahsulotlar qanday ko'rinishi.
 * grid — rasmli kartalar (do'kon, portfolio, uylar), list — ixcham ro'yxat (menyu, narxnoma).
 */
export const VITRINA_TYPES: Record<VitrinaType, { icon: typeof Store; tone: string; layout: "grid" | "list" }> = {
   shop: { icon: Store, tone: "59 130 246", layout: "grid" },
   menu: { icon: UtensilsCrossed, tone: "245 158 11", layout: "list" },
   portfolio: { icon: Images, tone: "139 92 246", layout: "grid" },
   pricelist: { icon: ReceiptText, tone: "20 184 166", layout: "list" },
   realty: { icon: Building2, tone: "236 72 153", layout: "grid" },
};

export const VITRINA_TYPE_KEYS = Object.keys(VITRINA_TYPES) as VitrinaType[];

export function storageUrl(path: string | null | undefined): string | null {
   return path ? `/storage/${path}` : null;
}

/** "45 000 so'm / porsiya", "60 000 so'mdan", "Kelishiladi"; narxsiz (portfolio) — bo'sh satr */
export function itemPrice(item: IVitrinaItem): string {
   if (item.price_mode === "negotiable") return t("vitrina.negotiable");
   if (item.price_mode === "none" || item.price === null) return "";

   const price = formatProductPrice(item.price, item.price_type ?? undefined);
   const withUnit = item.unit ? `${price} / ${item.unit}` : price;
   return item.price_mode === "from" ? t("vitrina.priceFrom", { price: withUnit }) : withUnit;
}

/** Telefon raqamiga qo'ng'iroq (bazada 93-123-45-67 ko'rinishida) */
export function callPhone(phone: string) {
   const digits = phone.replace(/\D/g, "");
   const full = digits.length === 9 ? `+998${digits}` : phone;
   if (isTMA()) window.open(`tel:${full}`);
   else window.location.href = `tel:${full}`;
}

/** Telegram'da yozish; text — tayyor xabar ("«Divan» haqida so'ramoqchi edim") */
export function openTelegram(username: string, text?: string) {
   const path = `${username.replace(/^@/, "")}${text ? `?text=${encodeURIComponent(text)}` : ""}`;
   if (isTMA()) postEvent("web_app_open_tg_link", { path_full: path });
   else window.open(`https://t.me/${path}`, "_blank", "noopener");
}

export function openUrl(url: string) {
   if (isTMA()) postEvent("web_app_open_link", { url });
   else window.open(url, "_blank", "noopener");
}

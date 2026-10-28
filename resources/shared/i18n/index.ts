import { createI18n } from "vue-i18n";
import { api } from "@shared/composables/useFetch";
import uz, { type MessageSchema } from "./locales/uz";
import ru from "./locales/ru";
import en from "./locales/en";

/**
 * Ilova tillari. Yangi matn qo'shganda uchala faylga ham yoziladi (locales/uz.ts — asosiy;
 * ru.ts va en.ts uning turiga bog'langan: kalit yetishmasa yoki ortiqcha bo'lsa TypeScript xato beradi).
 * Bir xil so'z ikki joyda takrorlanmasin — umumiylari `common` ichida.
 */
export const LOCALES = [
   { code: "uz", label: "O'zbek" },
   { code: "ru", label: "Русский" },
   { code: "en", label: "English" },
] as const;

export type Locale = (typeof LOCALES)[number]["code"];

const STORAGE_KEY = "locale";
/** Qo'lda tanlangan paytdagi tizim (Telegram) tili — u o'zgarsa, tanlov eskirgan hisoblanadi */
const SYSTEM_KEY = "locale_system";

let systemLocale: Locale = "uz";

declare module "vue-i18n" {
   // t("...") kalitlarini tekshirish va avtoto'ldirish uchun
   export interface DefineLocaleMessage extends MessageSchema {}
}

/** Rus tili: 1 e'lon / 2 e'lona / 5 e'lonov — "a | b | c" ko'rinishidagi matnlar uchun */
function russianPlural(choice: number, choicesLength: number) {
   if (choicesLength < 3) return choice === 1 ? 0 : 1;
   const mod10 = choice % 10;
   const mod100 = choice % 100;
   if (mod10 === 1 && mod100 !== 11) return 0;
   if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 1;
   return 2;
}

export const i18n = createI18n<[MessageSchema], Locale, false>({
   legacy: false,
   locale: "uz",
   fallbackLocale: "uz",
   messages: { uz, ru, en },
   pluralRules: { ru: russianPlural },
   missingWarn: false,
   fallbackWarn: false,
});

/** Komponentdan tashqarida (formatters, composable, zod sxemalari) */
export const t = i18n.global.t;

export function currentLocale(): Locale {
   return i18n.global.locale.value;
}

function normalize(code: string | null | undefined): Locale | null {
   const value = (code ?? "").toLowerCase();
   if (value.startsWith("uz")) return "uz";
   // MDH tillari: ruscha interfeys o'zbekcha/inglizchadan ko'ra tushunarliroq
   if (/^(ru|uk|be|kk|ky|tg)/.test(value)) return "ru";
   if (value.startsWith("en")) return "en";
   return null;
}

/**
 * Tartib: foydalanuvchi o'zi tanlagan til → Telegram tili (tizim) → brauzer tili → o'zbekcha.
 * Boshqa tillar (turk, nemis...) — inglizcha.
 * Qo'lda tanlangan til Telegram tili o'zgarmaguncha amal qiladi: Telegram'da til almashtirilsa,
 * ilova ham unga ergashadi (tepadagi Telegram tugmalari bilan bir tilda bo'lsin).
 */
export function detectLocale(telegramLanguageCode?: string | null): Locale {
   const source = telegramLanguageCode || navigator.language;
   systemLocale = source ? (normalize(source) ?? "en") : "uz";

   let saved: string | null = null;
   let savedSystem: string | null = null;
   try {
      saved = localStorage.getItem(STORAGE_KEY);
      savedSystem = localStorage.getItem(SYSTEM_KEY);
   } catch {
      // Maxfiy rejim — saqlab bo'lmaydi, avtomatik aniqlaymiz
   }

   const fromSaved = normalize(saved);
   if (fromSaved && savedSystem === systemLocale) return fromSaved;

   return systemLocale;
}

/** Tilni qo'llash: matnlar, <html lang>, server javoblari (X-Locale) */
export function applyLocale(locale: Locale, remember = false) {
   i18n.global.locale.value = locale;
   document.documentElement.lang = locale;
   api.defaults.headers.common["X-Locale"] = locale;

   if (remember) {
      try {
         localStorage.setItem(STORAGE_KEY, locale);
         localStorage.setItem(SYSTEM_KEY, systemLocale);
      } catch {
         // saqlab bo'lmasa ham joriy sessiyada ishlaydi
      }
   }
}

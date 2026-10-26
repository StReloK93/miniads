import type { RouteLocationRaw, Router } from "vue-router";

/**
 * Ilova ichida orqaga qaytadigan sahifa bormi.
 * window.history.length ishonchsiz (Telegram WebView ilovadan oldingi yozuvlarni ham sanaydi),
 * vue-router esa har bir yozuvga oldingi manzilni `state.back` qilib yozadi.
 */
export function canGoBack(): boolean {
   return Boolean(window.history.state?.back);
}

/**
 * Orqaga qaytadi; tarix bo'lmasa (masalan bot havolasi orqali to'g'ridan-to'g'ri e'lon ochilgan) — bosh sahifaga.
 */
export function goBack(router: Router, fallback: RouteLocationRaw = { name: "home" }) {
   if (canGoBack()) {
      router.back();
   } else {
      router.replace(fallback);
   }
}

/**
 * Ochiq oynalar (rasm preview, modal) uchun "orqaga" ushlagichlari.
 * Telegram'ning orqaga tugmasi avval eng oxirgi ochilgan oynani yopadi, sahifani emas.
 */
const backHandlers: (() => void)[] = [];

export function pushBackHandler(handler: () => void): () => void {
   backHandlers.push(handler);
   return () => {
      const index = backHandlers.lastIndexOf(handler);
      if (index >= 0) backHandlers.splice(index, 1);
   };
}

/** Ushlagich bo'lsa uni bajaradi va true qaytaradi */
export function runBackHandler(): boolean {
   const handler = backHandlers[backHandlers.length - 1];
   if (!handler) return false;
   handler();
   return true;
}

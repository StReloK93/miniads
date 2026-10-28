import { api } from "@shared/composables/useFetch";

/**
 * Faollik statistikasi: serverga ko'rinmaydigan harakatlar (ilova ochilishi, sahifa o'tishlari, aloqa tugmalari).
 * Yuk bo'lmasligi uchun: hodisa faqat xotiradagi navbatga qo'shiladi, ~30 soniyada bir marta (yoki ilova
 * yopilayotganda) bitta paket bo'lib fonda ketadi. Javob kutilmaydi, xato bo'lsa jim tashlab yuboriladi.
 * E'lon/kategoriya ko'rish, e'lon qo'yish kabi API so'rovlarini server o'zi yozadi (TrackActivity).
 */
type ActivityEvent = { e: "app_open" | "page" | "contact_phone" | "contact_telegram"; r?: string | null };

const FLUSH_INTERVAL = 30_000;
const MAX_QUEUE = 50;

const queue: ActivityEvent[] = [];

function newSessionId() {
   try {
      return crypto.randomUUID();
   } catch {
      return Math.random().toString(36).slice(2) + Date.now().toString(36);
   }
}

/** Ilova ochilgan har safar yangi: mehmonlarni ham "kishi" sifatida sanash uchun */
export const sessionId = newSessionId();

export function track(event: ActivityEvent["e"], ref?: string | number | null) {
   queue.push({ e: event, r: ref == null ? null : String(ref) });
   if (queue.length >= MAX_QUEUE) flush();
}

function flush() {
   if (!queue.length) return;
   const events = queue.splice(0, queue.length);
   const common = api.defaults.headers.common as Record<string, string | undefined>;

   const headers: Record<string, string> = { "Content-Type": "application/json", Accept: "application/json" };
   for (const name of ["Authorization", "X-Locale", "X-Client", "X-Session"]) {
      if (common[name]) headers[name] = String(common[name]);
   }

   try {
      // keepalive: ilova yopilayotganda ham so'rov oxiriga yetadi (sendBeacon'dan farqli — token yuboriladi)
      fetch("/api/activity", { method: "POST", keepalive: true, headers, body: JSON.stringify({ events }) }).catch(
         () => {},
      );
   } catch {
      // statistika ilovani hech qachon buzmasin
   }
}

export function startActivityTracking() {
   api.defaults.headers.common["X-Session"] = sessionId;

   setInterval(flush, FLUSH_INTERVAL);
   document.addEventListener("visibilitychange", () => {
      if (document.visibilityState === "hidden") flush();
   });
   window.addEventListener("pagehide", flush);
}

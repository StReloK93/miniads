import { api } from "@shared/composables/useFetch";

/**
 * Ilovadagi xatoni serverga yuboradi — u admin chatiga bot orqali ogohlantirish jo'natadi.
 * Hech qachon xato tashlamaydi: xabar berish asosiy amalni buzmasligi kerak.
 * Bir sessiyada bir xil xato bir marta yuboriladi.
 */
const sent = new Set<string>();

export function reportClientError(kind: string, message: string, details: Record<string, unknown> = {}) {
   const key = `${kind}:${message}`;
   if (sent.has(key) || sent.size > 20) return;
   sent.add(key);

   api.post("client-errors", {
      kind,
      message: String(message).slice(0, 500),
      page: window.location.pathname + window.location.search,
      details,
   }).catch(() => {
      // internet yo'q — jim o'tamiz
   });
}

/**
 * Forma yuborilmadi: server o'zi xabar bermaydigan holatlar (internet, nginx 413/502/504, 404...).
 * 422 — foydalanuvchi xatosi, 401 va Laravel 500 — server o'zi xabar beradi, 403 — bloklangan (kutilgan).
 */
export function reportRequestError(error: any, action: string) {
   const response = error?.response;
   const status: number | undefined = response?.status;
   if (status === 422 || status === 401 || status === 403) return;
   if (status && status >= 500 && (response.data?.code === "INTERNAL_SERVER_ERROR" || response.data?.exception)) return;

   reportClientError("request", `${action}: ${status ?? "tarmoq xatosi"} ${error?.message ?? ""}`.trim(), {
      url: error?.config?.url,
      method: error?.config?.method,
      status: status ?? null,
      online: navigator.onLine,
   });
}

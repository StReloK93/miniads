import type { Router } from "vue-router";

/**
 * Yangi versiya joylansa eski fayllar (build/assets/*-HASH.js) serverdan o'chadi. Ilovani oldin ochgan
 * foydalanuvchi boshqa sahifaga o'tganda "Failed to fetch dynamically imported module" chiqadi.
 * Bunday holatda sahifani bir marta yangilaymiz — brauzer yangi versiyani yuklaydi.
 */
const RELOAD_KEY = "stale-chunk-reload";

export function isStaleChunkError(error: unknown): boolean {
   const message = String((error as any)?.message ?? error ?? "");
   return /Failed to fetch dynamically imported module|Importing a module script failed|error loading dynamically imported module|Unable to preload CSS/i.test(
      message,
   );
}

function reloadOnce(url?: string): boolean {
   // Sikl bo'lmasin: 30 soniya ichida qayta yangilanmaydi (fayl haqiqatan yo'q bo'lsa xato odatdagidek chiqadi)
   const last = Number(sessionStorage.getItem(RELOAD_KEY) || 0);
   if (Date.now() - last < 30_000) return false;
   sessionStorage.setItem(RELOAD_KEY, String(Date.now()));
   if (url) window.location.assign(url);
   else window.location.reload();
   return true;
}

export function setupStaleChunkReload(router: Router): void {
   window.addEventListener("vite:preloadError", (event) => {
      if (reloadOnce()) event.preventDefault();
   });
   router.onError((error, to) => {
      if (isStaleChunkError(error)) reloadOnce(to?.fullPath);
   });
}

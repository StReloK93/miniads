import { isTMA, postEvent } from "@tma.js/bridge";

/** E'lonlar avtomatik chiqadigan kanal: to'liq havola va "@nom" ko'rinishi */
export const channelUrl = globalThis.ENV?.TELEGRAM_CHANNEL_URL || "";

export const channelName = (() => {
   try {
      const path = new URL(channelUrl).pathname.replace(/^\/+/, "");
      return path ? `@${path}` : "";
   } catch {
      return "";
   }
})();

/** Kanalni Telegram ichida (Mini App) yoki yangi oynada ochadi */
export function openChannel() {
   if (!channelUrl) return;
   if (isTMA()) {
      postEvent("web_app_open_tg_link", { path_full: new URL(channelUrl).pathname });
   } else {
      window.open(channelUrl, "_blank", "noopener");
   }
}

import { on, postEvent } from "@tma.js/bridge";

// Telegram ichida (fullscreen) env(safe-area-inset-*) ko'pincha 0 bo'ladi:
// qurilma joyi (status bar, home indicator) + Telegram tugmalari joyini bridge hodisalaridan olamiz.
// Faqat Telegram ichida chaqiriladi; tashqarida CSS'dagi env() qiymatlari qoladi.
export const setupTelegramSafeArea = () => {
   const device = { top: 0, bottom: 0 };
   const content = { top: 0, bottom: 0 };
   const root = document.documentElement.style;

   const apply = () => {
      root.setProperty("--safe-area-top", `${device.top + content.top}px`);
      root.setProperty("--safe-area-bottom", `${device.bottom + content.bottom}px`);
   };

   on("safe_area_changed", ({ top, bottom }) => {
      Object.assign(device, { top, bottom });
      apply();
   });

   on("content_safe_area_changed", ({ top, bottom }) => {
      Object.assign(content, { top, bottom });
      apply();
   });

   postEvent("web_app_request_safe_area");
   postEvent("web_app_request_content_safe_area");
};

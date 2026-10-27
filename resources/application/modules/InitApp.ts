import { postEvent } from "@tma.js/bridge";
import { setupTelegramSafeArea } from "@shared/modules/telegramSafeArea";
export const setupTMAUI = () => {
   postEvent("web_app_setup_swipe_behavior", { allow_vertical_swipe: false });
   postEvent("web_app_expand");
   setupTelegramSafeArea();
   // Yopishda tasdiq so'rash faqat forma sahifalarida yoqiladi (router/index.ts, meta.confirmClose)
};

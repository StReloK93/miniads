import type { Router } from "vue-router";
import { isTMA, on, postEvent } from "@tma.js/bridge";

/**
 * Admin panel mini app ichida oddiy havola (/admin) bilan alohida sahifa bo'lib ochiladi.
 * Telegram'ning tepadagi "orqaga" tugmasi ilovadan ko'rinib qoladi, lekin bu yerda unga hech kim
 * quloq solmasdi — bosilsa hech narsa bo'lmasdi. Endi:
 * 1) fokusdagi maydon bo'lsa — klaviatura yopiladi;
 * 2) ochiq oyna (drawer, modal) yoki menyu bo'lsa — o'sha yopiladi (Escape bilan, ular shunga yopiladi);
 * 3) admin panel ichida oldingi sahifa bo'lsa — unga qaytadi;
 * 4) aks holda — mini app'ga (admin panel qayerdan ochilgan bo'lsa).
 */
export function setupTelegramBackButton(router: Router) {
   if (!isTMA()) return;

   postEvent("web_app_setup_back_button", { is_visible: true });
   // Ilovaning forma sahifasidan qolgan "yopishda tasdiq so'ra" sozlamasi bu yerda kerak emas
   postEvent("web_app_setup_closing_behavior", { need_confirmation: false });

   on("back_button_pressed", () => {
      const active = document.activeElement as HTMLElement | null;
      if (active && ["INPUT", "TEXTAREA", "SELECT"].includes(active.tagName)) {
         active.blur();
         return;
      }

      if (document.querySelector('[role="dialog"]') || document.querySelector(".admin-shell.is-menu-open")) {
         document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
         return;
      }

      if (window.history.state?.back) {
         router.back();
         return;
      }

      // Ilovadan kelgan bo'lsa — o'sha sahifaga (tarix saqlanadi), bo'lmasa ilova profiliga
      const fromApp = document.referrer.startsWith(window.location.origin) && !new URL(document.referrer).pathname.startsWith("/admin");
      if (fromApp && window.history.length > 1) {
         window.history.back();
      } else {
         window.location.replace("/profile");
      }
   });
}

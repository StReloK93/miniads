import { postEvent, on } from "@tma.js/bridge";

export const setupTMAUI = () => {
   postEvent("web_app_setup_swipe_behavior", { allow_vertical_swipe: false });
   postEvent("web_app_expand");
   postEvent("web_app_request_content_safe_area");

   on("content_safe_area_changed", (payload) => {
      document.documentElement.style.setProperty("--safe-area-top", `${payload.top + 26}px`);
      document.documentElement.style.setProperty("--safe-area-bottom", "37px");
   });
};

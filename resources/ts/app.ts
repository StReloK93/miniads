import "primeicons/primeicons.css";
import { createApp } from "vue";
import { isTMA, retrieveRawInitData, postEvent, on } from "@tma.js/bridge";
import App from "./App.vue";
import PrimeVue from "primevue/config";
import { initTheme } from "./modules/theme";
import router from "./router";
import { MyTheme } from "./modules/PrimeVueTheme";
import { createPinia } from "pinia";
import { useAuth } from "./store/useAuth";
import ConfirmationService from "primevue/confirmationservice";
initTheme();
const app = createApp(App);
app.use(PrimeVue, MyTheme);
app.use(createPinia());
app.use(router);
app.use(ConfirmationService);
if (isTMA()) {
   postEvent("web_app_setup_swipe_behavior", { allow_vertical_swipe: false });
   const AuthStore = useAuth();

   on("content_safe_area_changed", async (payload) => {
      document.documentElement.style.setProperty("--safe-area-top", `${payload.top + 20}px`);
      document.documentElement.style.setProperty("--safe-area-bottom", `${37}px`);
   });

   postEvent("web_app_request_content_safe_area");
   postEvent("web_app_expand");
   await AuthStore.getUser()
      .catch(async () => {
         const init = retrieveRawInitData();
         if (init) {
            await AuthStore.signInTelegram(init).catch(() => console.log("User phone not found"));
         }
      })
      .finally(() => app.mount("#app"));
} else {
   app.mount("#app");
}

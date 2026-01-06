import "primeicons/primeicons.css";
import { createApp } from "vue";
import { isTMA, retrieveRawInitData, postEvent, on } from "@tma.js/bridge";
import App from "./App.vue";
import PrimeVue from "primevue/config";
import { initTheme } from "./components/features/theme";
import router from "./router";
import { MyTheme } from "./configs/PrimeVueTheme";
import { createPinia } from "pinia";
import { useAuthStore } from "./store/useAuthStore";
initTheme();
// 2. Kengaytirilgan komponentni 'Button' nomi bilan ro'yxatdan o'tkazamiz
const app = createApp(App);
app.use(PrimeVue, MyTheme);
app.use(createPinia());
app.use(router);

if (isTMA()) {
   postEvent("web_app_setup_swipe_behavior", { allow_vertical_swipe: false });

   const AuthStore = useAuthStore();

   on("content_safe_area_changed", async (payload) => {
      AuthStore.safe_area = payload;
      document.documentElement.style.setProperty("--safe-area-top", `${payload.top}px`);
      console.log(AuthStore.safe_area);
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

import { createApp } from "vue";
import { createPinia } from "pinia";
import PrimeVue from "primevue/config";
import ConfirmationService from "primevue/confirmationservice";
import { isTMA, retrieveRawInitData, postEvent, on } from "@tma.js/bridge";

import App from "./App.vue";
import router from "./router";
import { useAuth } from "./store/useAuth";
import { initTheme } from "./modules/theme";
import { MyTheme } from "./modules/PrimeVueTheme";

// 1. Ilovani tayyorlash
initTheme();
const app = createApp(App);

app.use(createPinia()); // Pinia birinchi bo'lishi shart (Store ishlatish uchun)
app.use(PrimeVue, MyTheme);
app.use(ConfirmationService);

const authStore = useAuth();

// 2. Telegram Mini App UI sozlamalari
const setupTMAUI = () => {
   postEvent("web_app_setup_swipe_behavior", { allow_vertical_swipe: false });
   postEvent("web_app_expand");
   postEvent("web_app_request_content_safe_area");

   on("content_safe_area_changed", (payload) => {
      document.documentElement.style.setProperty("--safe-area-top", `${payload.top + 26}px`);
      document.documentElement.style.setProperty("--safe-area-bottom", "37px");
   });
};

// 3. Asosiy yuklanish logikasi (Auth + Mount)
const initApp = async () => {
   if (isTMA()) setupTMAUI();

   try {
      // Avval foydalanuvchini tekshiramiz
      await authStore.getUser();
   } catch (error) {
      // Agar user yo'q bo'lsa va bu TMA bo'lsa, login qilamiz
      const initData = isTMA() ? retrieveRawInitData() : null;
      if (initData) {
         await authStore.signInTelegram(initData).catch(() => console.warn("TMA Auth failed"));
      }
   } finally {
      // Nima bo'lganda ham ilovani ekranga chiqaramiz
      app.use(router).mount("#app");
   }
};

initApp();

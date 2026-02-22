import { createApp } from "vue";
import { createPinia } from "pinia";
import { isTMA, retrieveRawInitData, retrieveLaunchParams } from "@tma.js/bridge";
import App from "@/App.vue";
import router from "@/router";
import { setupTMAUI } from "@/modules/InitApp";
import { useAuth } from "@shared/store/useAuth";
import { initTheme } from "@shared/components/theme";
import "@shared/css/ui.scss";
initTheme();
const app = createApp(App);
app.use(createPinia());

const authStore = useAuth();

// 3. Asosiy yuklanish logikasi (Auth + Mount)
const initApp = async () => {
   const tma = isTMA();
   if (tma) {
      const initData = tma ? retrieveRawInitData() : null;
      setupTMAUI();
      const telegramData = retrieveLaunchParams();
   }
   try {
      await authStore.getUser();
   } catch (error) {
      const initData = tma ? retrieveRawInitData() : null;

      if (initData) {
         await authStore.signInTelegram(initData).catch(() => console.warn("TMA Auth failed"));
      }
   } finally {
      app.use(router).provide("tma", tma).mount("#app");
   }
};

initApp();

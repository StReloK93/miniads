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
   var userData: any = null;
   var initData: any = null;
   const tma = isTMA();
   if (tma) {
      initData = tma ? retrieveRawInitData() : null;
      setupTMAUI();
      userData = retrieveLaunchParams();
   } else {
      await authStore.getUser();
   }
   try {
      if (initData) {
         await authStore.signInTelegram(initData).catch(() => console.warn("TMA Auth failed"));
      }
   } catch (error) {
      if (initData) {
         await authStore.signInTelegram(initData).catch(() => console.warn("TMA Auth failed"));
      }
   } finally {
      app.use(router).provide("userData", userData).mount("#app");
   }
};

initApp();

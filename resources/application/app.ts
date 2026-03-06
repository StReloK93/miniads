import { createApp } from "vue";
import { createPinia } from "pinia";
import { isTMA, retrieveRawInitData, retrieveLaunchParams } from "@tma.js/bridge";
import App from "@/App.vue";
import router from "@/router";
import { setupTMAUI } from "@/modules/InitApp";
import { useAuth } from "@shared/store/useAuth";
import { initTheme } from "@shared/composables/useTheme";
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

   try {
      if (tma) {
         initData = tma ? retrieveRawInitData() : null;
         setupTMAUI();
         userData = retrieveLaunchParams();
         await authStore.signInTelegram(initData).catch(() => console.warn("TMA Auth failed"));
      } else {
         await authStore.getUser();
         if (import.meta.env.DEV && !authStore.user) {
            await authStore.testAuth();
         }
      }
   } catch (error) {
   } finally {
      app.use(router).provide("userData", userData).mount("#app");
   }
};

initApp();

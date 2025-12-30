import "primeicons/primeicons.css";
import { createApp } from "vue";
import { isTMA, retrieveRawInitData } from "@tma.js/bridge";
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
   const AuthStore = useAuthStore();
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

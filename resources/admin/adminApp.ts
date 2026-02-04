import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "@admin/AdminApp.vue";
import router from "@admin/router";
import ConfirmationService from "primevue/confirmationservice";
import PrimeVue from "primevue/config";
import "primeicons/primeicons.css";
import { MyTheme } from "./modules/PrimeVueTheme";
import { useAuth } from "@shared/store/useAuth";
const app = createApp(App);
app.use(createPinia());

app.use(PrimeVue, MyTheme);
app.use(ConfirmationService);

// 3. Asosiy yuklanish logikasi (Auth + Mount)
const initApp = async () => {
   const authStore = useAuth();
   try {
      await authStore.getUser();
   } catch (error) {
   } finally {
      // Nima bo'lganda ham ilovani ekranga chiqaramiz
      app.use(router).mount("#app");
   }
};

initApp();

import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "@admin/AdminApp.vue";
import router from "@admin/router";
import { useAuth } from "@shared/store/useAuth";
import { initTheme } from "@shared/composables/useTheme";
import "@shared/css/ui.scss";
import { responsiveTable } from "@admin/modules/responsiveTable";
initTheme();
const app = createApp(App);
app.use(createPinia());
// Telefonda jadval qatorlari kartaga aylanadi
app.directive("responsive-table", responsiveTable);

// 3. Asosiy yuklanish logikasi (Auth + Mount)
const initApp = async () => {
   const authStore = useAuth();
   try {
      await authStore.getUser();
   } catch (error) {
      console.error("Admin sessionini tiklab bo'lmadi.", error);
      window.location.replace("/");
      return;
   }

   if (authStore.user?.role !== "admin") {
      window.location.replace("/");
      return;
   }

   app.use(router).mount("#app");
};

initApp();

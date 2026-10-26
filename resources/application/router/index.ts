import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";
import { useAuth } from "@shared/store/useAuth";
import { postEvent } from "@tma.js/bridge";
import { isTMA } from "@tma.js/bridge";
const router = createRouter({
   history: createWebHistory(),
   routes,
});

// Telegram tugmalari yakuniy sahifaga qarab sozlanadi (yo'naltirishlardan keyin)
router.afterEach((to) => {
   if (!isTMA()) return;

   postEvent("web_app_setup_back_button", { is_visible: to.name !== "home" });
   // Forma to'ldirilayotganda tasodifan yopib yubormaslik uchun tasdiq so'raladi; boshqa sahifalarda — yo'q
   postEvent("web_app_setup_closing_behavior", { need_confirmation: Boolean(to.meta.confirmClose) });
});

router.beforeEach((to, from, next) => {
   const AuthStore = useAuth();

   if (to.meta.guestOnly && AuthStore.user) {
      return next({ name: "home" });
   }

   if (to.meta.requiresAuth && !AuthStore.user) {
      return next({ name: "login", query: { redirect: to.fullPath } });
   }

   return next();
});

export default router;

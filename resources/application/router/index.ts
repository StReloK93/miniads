import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";
import { useAuth } from "@shared/store/useAuth";
import { postEvent } from "@tma.js/bridge";
import { isTMA } from "@tma.js/bridge";
const router = createRouter({
   history: createWebHistory(),
   routes,
});

router.beforeEach((to, from, next) => {
   if (isTMA()) {
      if (to.name == "home") {
         postEvent("web_app_setup_back_button", { is_visible: false });
      } else {
         postEvent("web_app_setup_back_button", { is_visible: true });
      }
   }

   const AuthStore = useAuth();

   if (to.meta.guestOnly && AuthStore.user) {
      return next({ name: "home" });
   }
   return next();
});

export default router;

import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";
import { useAuth } from "@/store/useAuth";

const router = createRouter({
   history: createWebHistory(),
   routes,
});

router.beforeEach((to, from, next) => {
   const AuthStore = useAuth();

   if (to.meta.guestOnly && AuthStore.user) {
      return next({ name: "home" });
   }
   return next();
});

export default router;

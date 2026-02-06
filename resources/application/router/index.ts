import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";
import { useAuth } from "@shared/store/useAuth";

const router = createRouter({
   history: createWebHistory(),
   routes,
});
const routeStack: string[] = [];
const MAX_STACK = 3;

router.beforeEach((to, from, next) => {
   const toPath = to.fullPath;
   const lastIndex = routeStack.lastIndexOf(toPath);

   if (lastIndex !== -1 && lastIndex < routeStack.length - 1) {
      // Oldingi sahifaga qaytildi
      to.meta.transition = "slide-out";

      // stackni shu joygacha qisqartiramiz
      routeStack.splice(lastIndex + 1);
   } else {
      // Yangi sahifa
      to.meta.transition = "slide-in";
      console.log(routeStack);

      routeStack.push(toPath);

      if (routeStack.length > MAX_STACK) {
         routeStack.shift();
      }
   }

   const AuthStore = useAuth();

   if (to.meta.guestOnly && AuthStore.user) {
      return next({ name: "home" });
   }
   return next();
});

export default router;

import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";
import { useAuthStore } from "@/store/useAuthStore";

const router = createRouter({
   history: createWebHistory(),
   routes,
});

router.beforeEach((to, from, next) => {
   const AuthStore = useAuthStore();
   console.log(AuthStore.user);

   next();
});

export default router;

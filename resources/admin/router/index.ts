import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";
import { useAuth } from "@shared/store/useAuth";
const router = createRouter({
   history: createWebHistory(),
   routes,
});

router.beforeEach((to) => {
   const auth = useAuth();
   if (auth.user?.role === "admin") return true;
   return { path: "/", replace: true };
});

export default router;

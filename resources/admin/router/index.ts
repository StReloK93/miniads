import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";
// import { useAuth } from "@shared/store/useAuth";
const router = createRouter({
   history: createWebHistory(),
   routes,
});

router.beforeEach((to, from, next) => {
   // const AuthStore = useAuth();
   // console.log(AuthStore.user);
   // if (AuthStore.user && AuthStore.user.role == "admin") {
   //    return next();
   // } else {
   //    // shu yerda redirect qilish kerak lekin admin sahifamda "/" bu yoq
   //    window.location.href = "/";
   // }

   return next();
});

export default router;

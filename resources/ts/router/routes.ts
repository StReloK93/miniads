import Home from "@pages/application/HomePage.vue";
import Login from "@pages/application/LoginPage.vue";
import { RouteRecordRaw } from "vue-router";
import Application from "@/layouts/Application.vue";
export const routes: RouteRecordRaw[] = [
   {
      path: "/",
      component: Application,
      redirect: { name: "home" },
      children: [{ path: "", component: Home, name: "home" }],
   },
   { path: "/categories", component: () => import("@pages/application/CategoryPage.vue"), name: "categories" },
   { path: "/login", component: Login },
];

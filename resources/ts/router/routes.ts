import Home from "@pages/application/HomePage.vue";
import AdminHome from "@pages/admin/AdminHome.vue";
import Login from "@pages/application/LoginPage.vue";
import { RouteRecordRaw } from "vue-router";
import Application from "@/layouts/ApplicationLayout.vue";
import Admin from "@/layouts/AdminLayout.vue";
export const routes: RouteRecordRaw[] = [
   {
      path: "/",
      component: Application,
      redirect: { name: "home" },
      children: [{ path: "", component: Home, name: "home" }],
   },
   { path: "/categories", component: () => import("@pages/application/CategoryPage.vue"), name: "categories" },
   { path: "/login", component: Login },
   {
      path: "/admin",
      component: Admin,
      redirect: { name: "admin-home" },
      children: [{ path: "", component: AdminHome, name: "admin-home" }],
   },
];

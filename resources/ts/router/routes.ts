import Home from "@pages/application/HomePage.vue";
import Login from "@pages/application/LoginPage.vue";
import { RouteRecordRaw } from "vue-router";
import Application from "@/pages/layouts/AppLayout.vue";
import Admin from "@/pages/layouts/AdminLayout.vue";
import CategoryIdPage from "@/pages/application/CategoryIdPage.vue";
import FavoritePage from "@/pages/application/FavoritePage.vue";
import ProductPage from "@/pages/application/ProductPage.vue";
export const routes: RouteRecordRaw[] = [
   {
      path: "/",
      component: Application,
      redirect: { name: "home" },
      children: [
         { path: "", component: Home, name: "home" },
         { path: "/favorites", component: FavoritePage, name: "favorites" },
         { path: "/category/:id", component: CategoryIdPage, name: "category-id", props: true },
         {
            path: "/categories",
            component: () => import("@pages/application/CategoryPage.vue"),
            name: "categories",
         },
      ],
   },

   { path: "/product/:id", component: ProductPage, name: "product-id", props: true },
   { path: "/login", component: Login, meta: { guestOnly: true }, name: "login" },
   {
      path: "/admin",
      component: Admin,
      redirect: { name: "admin-categories" },
      children: [
         // {
         //    path: "",
         //    component: () => import("@pages/admin/AdminHomePage.vue"),
         //    name: "admin-home",
         // },
         {
            path: "categories",
            component: () => import("@pages/admin/AdminCategoriesPage.vue"),
            name: "admin-categories",
         },
         {
            path: "parameter",
            component: () => import("@pages/admin/AdminParametersPage.vue"),
            name: "admin-parameters",
         },
      ],
   },
];

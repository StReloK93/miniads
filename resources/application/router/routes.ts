import Home from "@pages/HomePage.vue";
import Login from "@pages/LoginPage.vue";
import { RouteRecordRaw } from "vue-router";
import Application from "@pages/AppLayout.vue";
import CategoryIdPage from "@pages/CategoryIdPage.vue";
import FavoritePage from "@pages/FavoritePage.vue";
import ProductPage from "@pages/ProductPage.vue";
import CreateProductPage from "@/pages/CreateProductPage.vue";
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
            component: () => import("@/pages/CategoryPage.vue"),
            name: "categories",
         },
      ],
   },

   { path: "/product/:id", component: ProductPage, name: "product-id", props: true },
   { path: "/login", component: Login, meta: { guestOnly: true }, name: "login" },
   { path: "/create-product", component: CreateProductPage, name: "create-product" },
];

import { RouteRecordRaw } from "vue-router";
export const routes: RouteRecordRaw[] = [
   {
      path: "/",
      component: () => import("@/layouts/NavigationPage.vue"),
      redirect: { name: "home" },
      children: [
         { path: "", component: () => import("@/pages/HomePage.vue"), name: "home" },
         { path: "/favorites", component: () => import("@/pages/FavoritePage.vue"), name: "favorites" },
         { path: "/profile", component: () => import("@/pages/ProfilePage.vue"), name: "profile" },
         {
            path: "/category/:id",
            component: () => import("@/pages/CategoryIdPage.vue"),
            name: "category",
            props: true,
         },
         {
            path: "/categories/:id?",
            component: () => import("@/pages/CategoriesPage.vue"),
            name: "categories",
         },
      ],
   },

   { path: "/product/:id", component: () => import("@/pages/ProductPage.vue"), name: "product-id", props: true },
   { path: "/login", component: () => import("@/pages/LoginPage.vue"), meta: { guestOnly: true }, name: "login" },
   { path: "/create-product", component: () => import("@/pages/CreateProductPage.vue"), name: "create-product" },
];

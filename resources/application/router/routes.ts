import { RouteRecordRaw } from "vue-router";

export const routes: RouteRecordRaw[] = [
   {
      path: "/",
      component: () => import("@/layouts/NavigationPage.vue"),
      // redirect: { name: "home" },
      meta: { layoutKey: "nav" },
      children: [
         { path: "", component: () => import("@pages/HomePage.vue"), name: "home" },
         { path: "favorites", component: () => import("@pages/FavoritePage.vue"), name: "favorites" },
         { path: "profile", component: () => import("@pages/ProfilePage.vue"), name: "profile" },
         {
            path: "category/:id",
            component: () => import("@pages/CategoryIdPage.vue"),
            name: "category",
            props: true,
         },
      ],
   },
   {
      path: "/",
      component: () => import("@/layouts/FullPage.vue"),
      meta: { layoutKey: "full" },
      children: [
         {
            path: "search",
            component: () => import("@pages/SearchPage.vue"),
            name: "search",
         },
         {
            path: "categories",
            component: () => import("@pages/CategoriesPage.vue"),
            name: "categories",
            props: true,
         },
         {
            path: "select-category",
            props: true,
            component: () => import("@pages/Create/SelectCategoryPage.vue"),
            name: "create-select-category",
         },
         {
            path: "create-product/:categoryId",
            props: true,
            component: () => import("@pages/Create/CreateProductPage.vue"),
            name: "create-product",
         },

         { path: "login", component: () => import("@pages/LoginPage.vue"), meta: { guestOnly: true }, name: "login" },
      ],
   },
   { path: "/product/:id", component: () => import("@pages/ProductPage.vue"), name: "product-id", props: true },
];

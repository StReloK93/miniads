import { RouteRecordRaw } from "vue-router";

export const routes: RouteRecordRaw[] = [
   {
      path: "/",
      component: () => import("@/layouts/NavigationPage.vue"),
      // redirect: { name: "home" },
      meta: { layoutKey: "nav" },
      children: [
         { path: "", component: () => import("@pages/HomePage.vue"), name: "home" },
         {
            path: "categories",
            component: () => import("@pages/CategoriesPage.vue"),
            name: "categories",
            props: true,
         },
         { path: "profile", component: () => import("@pages/ProfilePage.vue"), name: "profile" },
         { path: "my-ads", component: () => import("@pages/MyAdsPage.vue"), name: "my-ads" },
         // Qidiruv endi bosh sahifaning o'zida (Telegram uslubi); eski havolalar uni ochadi
         { path: "search", name: "search", redirect: { name: "home", query: { search: "1" } } },
         {
            path: "category/:id",
            component: () => import("@pages/CategoryIdPage.vue"),
            name: "category",
            props: true,
         },
         { path: "favorites", component: () => import("@pages/FavoritePage.vue"), name: "favorites" },
      ],
   },
   {
      path: "/",
      component: () => import("@/layouts/FullPage.vue"),
      meta: { layoutKey: "full" },
      children: [
         {
            path: "select-category/:cityId?",
            props: true,
            component: () => import("@pages/Create/SelectCategoryPage.vue"),
            name: "create-select-category",
            meta: { requiresAuth: true },
         },
         {
            path: "select-city",
            props: true,
            component: () => import("@pages/Create/SelectCityPage.vue"),
            name: "create-select-city",
            meta: { requiresAuth: true },
         },
         {
            path: "create-product/:categoryId/:cityId?",
            props: true,
            component: () => import("@pages/Create/CreateProductPage.vue"),
            name: "create-product",
            meta: { requiresAuth: true, confirmClose: true },
         },
         {
            path: "edit-product/:product_id",
            // URL parametri doim satr ("17"); sahifa son kutadi
            props: (route) => ({ product_id: Number(route.params.product_id) }),
            component: () => import("@pages/Create/EditProductPage.vue"),
            name: "edit-product",
            meta: { requiresAuth: true, confirmClose: true },
         },
         { path: "login", component: () => import("@pages/LoginPage.vue"), meta: { guestOnly: true }, name: "login" },
      ],
   },
   { path: "/product/:id", component: () => import("@pages/ProductPage.vue"), name: "product-id", props: true },
];

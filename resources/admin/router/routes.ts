import { RouteRecordRaw } from "vue-router";
import Admin from "@admin/pages/AdminLayout.vue";
export const routes: RouteRecordRaw[] = [
   {
      path: "/admin",
      component: Admin,
      redirect: { name: "admin-home" },
      children: [
         {
            path: "",
            component: () => import("@admin/pages/AdminHomePage.vue"),
            name: "admin-home",
         },
         {
            path: "categories",
            component: () => import("@admin/pages/AdminCategoriesPage.vue"),
            name: "admin-categories",
         },
         {
            path: "products",
            component: () => import("@admin/pages/AdminProductsPage.vue"),
            name: "admin-products",
         },
         {
            path: "users",
            component: () => import("@admin/pages/AdminUsersPage.vue"),
            name: "admin-users",
         },
         {
            path: "bot",
            component: () => import("@admin/pages/AdminBotPage.vue"),
            name: "admin-bot",
         },
         {
            path: "parameter",
            component: () => import("@admin/pages/AdminParametersPage.vue"),
            name: "admin-parameters",
         },
         {
            path: "districts",
            component: () => import("@admin/pages/AdminReferencePage.vue"),
            props: { kind: "districts" },
            name: "admin-districts",
         },
         {
            path: "price-types",
            component: () => import("@admin/pages/AdminReferencePage.vue"),
            props: { kind: "price-types" },
            name: "admin-price-types",
         },
      ],
   },
];

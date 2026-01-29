import { RouteRecordRaw } from "vue-router";
import Admin from "@admin/pages/AdminLayout.vue";
export const routes: RouteRecordRaw[] = [
   {
      path: "/admin",
      component: Admin,
      redirect: { name: "admin-categories" },
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
            path: "parameter",
            component: () => import("@admin/pages/AdminParametersPage.vue"),
            name: "admin-parameters",
         },
      ],
   },
];

<template>
   <section class="w-dvw h-dvh overflow-hidden no-scrollbar relative">
      <RouterView v-slot="{ Component, route }">
         <transition :name="animationName" mode="out-in">
            <component :is="Component" :key="route.meta.layoutKey" @vue:mounted="onComponentMounted" />
         </transition>
      </RouterView>
   </section>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from "vue-router";

import { nextTick, onMounted, ref } from "vue";
import { useCategory } from "@shared/entities/Category/useCategory";
import { on, postEvent } from "@tma.js/bridge";
const categoryStore = useCategory();
const animationName = ref("");

const router = useRouter();
const route = useRoute();
function onComponentMounted() {
   animationName.value = "slide-bottom";
}

function canPrefetch(): boolean {
   const nav = navigator as Navigator & {
      connection?: {
         saveData?: boolean;
         effectiveType?: "slow-2g" | "2g" | "3g" | "4g";
      };
   };

   return (
      !nav.connection?.saveData && nav.connection?.effectiveType !== "2g" && nav.connection?.effectiveType !== "slow-2g"
   );
}

onMounted(async () => {
   await categoryStore.getParentCategories();
   await nextTick();
   const loader = document.getElementById("loader");

   loader?.remove();

   setTimeout(() => {
      if (canPrefetch()) {
         import("@pages/ProfilePage.vue");
         import("@pages/LoginPage.vue");
         import("@pages/CategoriesPage.vue");
         import("@pages/CategoryIdPage.vue");
         import("@pages/ProductPage.vue");
         import("@pages/SearchPage.vue");
         import("@pages/Create/SelectCategoryPage.vue");
         import("@pages/FavoritePage.vue");
         import("@/layouts/FullPage.vue");
      }
   }, 1500);

   // ===== BACK BOSILGANDA =====
   on("back_button_pressed", () => {
      if (route.name === "home") {
         postEvent("web_app_close");
      } else {
         router.back();
      }
   });
});
</script>

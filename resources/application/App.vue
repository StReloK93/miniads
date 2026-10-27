<template>
   <div class="min-h-dvh w-full flex justify-center bg-slate-100 dark:bg-[#070a12]">
      <section
         class="w-full h-dvh overflow-hidden no-scrollbar relative z-ambient sm:max-w-125 sm:border-x sm:border-(--z-border) sm:shadow-2xl"
      >
         <RouterView v-slot="{ Component, route }">
            <transition :name="animationName" mode="out-in">
               <component :is="Component" :key="route.meta.layoutKey" @vue:mounted="onComponentMounted" />
            </transition>
         </RouterView>
      </section>
   </div>
</template>

<script setup lang="ts">
import { useFocusedInput } from "@shared/composables/useFocusInput";
import { useRoute, useRouter } from "vue-router";

import { nextTick, onMounted, ref } from "vue";
import { useCategory } from "@shared/entities/Category/useCategory";
import { useCity } from "@shared/entities/District/useCity";
import { on, postEvent } from "@tma.js/bridge";
import { goBack, runBackHandler } from "@/modules/navigation";
const categoryStore = useCategory();
const cityStore = useCity();
const animationName = ref("");

const router = useRouter();
const route = useRoute();

const { hasFocusedInput, focusedElement } = useFocusedInput();
function onComponentMounted() {
   animationName.value = "page-rise";
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
   const startupTasks = await Promise.allSettled([categoryStore.getParentCategories(), cityStore.getCities()]);

   startupTasks.forEach((result, index) => {
      if (result.status === "rejected") {
         const taskName = index === 0 ? "parent categories" : "cities";
         console.error(`Unable to load ${taskName} during startup.`, result.reason);
      }
   });

   try {
      await nextTick();
   } finally {
      document.getElementById("loader")?.remove();
   }

   setTimeout(() => {
      if (canPrefetch()) {
         import("@pages/ProfilePage.vue");
         import("@pages/MyAdsPage.vue");
         import("@pages/LoginPage.vue");
         import("@pages/CategoriesPage.vue");
         import("@pages/CategoryIdPage.vue");
         import("@pages/ProductPage.vue");
         import("@pages/Create/SelectCategoryPage.vue");
         import("@pages/FavoritePage.vue");
         import("@/layouts/FullPage.vue");
      }
   }, 1500);

   // ===== BACK BOSILGANDA =====
   on("back_button_pressed", () => {
      if (hasFocusedInput.value) {
         focusedElement.value?.blur();
         return;
      }

      // Ochiq oyna bo'lsa (masalan rasm preview) — avval o'sha yopiladi
      if (runBackHandler()) return;

      if (route.name === "home") {
         postEvent("web_app_close");
      } else {
         goBack(router);
      }
   });
});
</script>

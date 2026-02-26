<template>
   <NavigationPageDecorator>
      <template #header>
         <h3 class="font-bold text-xl">Profil</h3>
         <aside class="flex flex-col items-center pb-6 py-4">
            <div class="mb-2">
               <img v-if="user.photo_url" :src="user.photo_url" class="rounded-full w-18 h-18 object-fill" />
               <span v-else class="rounded-full w-18 h-18 bg-slate-100 inline-flex justify-center items-center">
                  <User class="size-5" />
               </span>
            </div>
            <!-- {{ userData.tgWebAppData.user.photo_url }} -->
            <h3 class="font-semibold">{{ user?.first_name }} {{ user?.last_name }}</h3>
            <div class="text-(--z-muted-text) mb-6">@{{ user?.username }}</div>

            <main class="flex w-full divide-x divide-(--z-border)">
               <div class="text-center w-1/3">
                  <span class="font-semibold">45</span>
                  <h3 class="text-(--z-muted-text) text-sm">E'lon</h3>
               </div>
               <div class="text-center w-1/3">
                  <span class="font-semibold">12</span>
                  <h3 class="text-(--z-muted-text) text-sm">Kuzatuvchi</h3>
               </div>
               <div class="text-center w-1/3">
                  <span class="font-semibold">4.5</span>
                  <h3 class="text-(--z-muted-text) text-sm">Baho</h3>
               </div>
            </main>

            <div class="flex gap-2 w-full mt-8">
               <BaseButton size="sm" rounded class="w-full"> E'lonlar </BaseButton>
               <BaseButton size="sm" rounded class="w-full" severity="secondary"> Do'kon </BaseButton>
            </div>
         </aside>
      </template>
      <template #content>
         <BaseTabs :items="['Faol', `O'chiq`, 'Tekshiruvda']" @change="onTabChange" class="w-full" />
         <!-- <aside class="py-3 flex flex-col gap-1">
            <div class="bg-(--z-card) rounded-(--z-rounded) p-4 flex justify-between items-center">
               <span class="inline-flex gap-2 items-center">
                  <List class="size-4 inline text-(--z-primary)" />
                  <span> Mening e'lonlarim </span>
               </span>
               <span>
                  <ChevronRight class="size-4" />
               </span>
            </div>
            <RouterLink
               :to="{ name: 'favorites' }"
               class="bg-(--z-card) rounded-(--z-rounded) p-4 flex justify-between items-center"
            >
               <span class="inline-flex gap-2 items-center">
                  <Heart class="size-4 inline text-(--z-danger)" />
                  <span> Sevimlilar </span>
               </span>
               <span>
                  <ChevronRight class="size-4" />
               </span>
            </RouterLink>
            <div class="bg-(--z-card) rounded-(--z-rounded) p-4 flex justify-between items-center">
               <span class="inline-flex gap-2 items-center">
                  <Bell class="size-4 inline text-(--z-primary)" />
                  <span> Bildirishnomalar </span>
               </span>
               <span>
                  <ChevronRight class="size-4" />
               </span>
            </div>
         </aside> -->
      </template>
   </NavigationPageDecorator>
</template>

<script setup lang="ts">
import { computed, inject } from "vue";
import { useAuth } from "@shared/store/useAuth";

function onTabChange({ index, value }) {
   console.log(index, value); // 0, 1, 2
}

const userData: any = inject("userData");
const AuthStore = useAuth();
import NavigationPageDecorator from "@/components/NavigationPageDecorator.vue";
import { isTMA } from "@tma.js/bridge";
import { Bell, ChevronRight, Heart, List, User } from "lucide-vue-next";
import { RouterLink } from "vue-router";

const user = computed(() => {
   if (isTMA()) {
      console.log(userData?.tgWebAppData.user);

      return userData?.tgWebAppData.user;
   } else {
      return {
         photo_url: null,
         first_name: "Bumin",
         last_name: "Xoqon",
         username: "Ruzzifer",
      };
   }
});
</script>
